<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeOAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    /** @var string[] */
    private array $scopes;

    public function __construct()
    {
        $this->clientId     = config('youtube.client_id', '');
        $this->clientSecret = config('youtube.client_secret', '');
        $this->redirectUri  = config('youtube.redirect_uri', '');
        $this->scopes       = config('youtube.scopes', [
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtube',
        ]);
    }

    /**
     * Generates the Google OAuth2 authorization URL.
     *
     * @param  int  $tenantId  Embedded in state parameter for callback verification.
     */
    public function getAuthorizationUrl(int $tenantId): string
    {
        $params = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => implode(' ', $this->scopes),
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => base64_encode(json_encode([
                'tenant_id' => $tenantId,
                'sig'       => hash_hmac('sha256', (string) $tenantId, config('app.key')),
            ])),
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    /**
     * Exchanges an authorization code for access + refresh tokens.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int, token_type: string}
     * @throws \RuntimeException
     */
    public function exchangeCodeForTokens(string $code): array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'grant_type'    => 'authorization_code',
        ]);

        if (!$response->successful()) {
            Log::error('YouTube token exchange failed', ['response' => $response->body()]);
            throw new \RuntimeException('Failed to exchange YouTube authorization code: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Refreshes an expired access token using a refresh token.
     *
     * @return array{access_token: string, expires_in: int, token_type: string}
     * @throws \RuntimeException
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'refresh_token' => $refreshToken,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'refresh_token',
        ]);

        if (!$response->successful()) {
            Log::error('YouTube token refresh failed', ['response' => $response->body()]);
            throw new \RuntimeException('Failed to refresh YouTube access token: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Downloads a video from R2 and uploads it to YouTube using the resumable upload API.
     *
     * @param  array{
     *     title: string,
     *     description: string,
     *     tags: array,
     *     privacy: string,
     *     access_token: string
     * }  $videoData
     * @param  string       $r2Url       Public CDN URL of the video in R2.
     * @param  string|null  $thumbnailUrl  Optional thumbnail URL.
     * @return array{success: bool, video_id: string, url: string}
     * @throws \RuntimeException
     */
    public function uploadVideo(array $videoData, string $r2Url, ?string $thumbnailUrl = null): array
    {
        $accessToken = $videoData['access_token'] ?? '';

        // Step 1: Download video binary from R2
        Log::info('YouTube upload: downloading video from R2', ['url' => $r2Url]);
        $response = Http::timeout(300)->get($r2Url);

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to download video from R2 (HTTP {$response->status()}): {$response->body()}");
        }

        $videoBody = $response->body();

        if (empty($videoBody)) {
            throw new \RuntimeException('Failed to download video from R2 URL: ' . $r2Url);
        }

        // Step 2: Initiate resumable upload
        $metadata = [
            'snippet' => [
                'title'       => $videoData['title'] ?? 'Untitled',
                'description' => $videoData['description'] ?? '',
                'tags'        => $videoData['tags'] ?? [],
                'categoryId'  => '25', // News & Politics
            ],
            'status' => [
                'privacyStatus' => $videoData['privacy'] ?? 'public',
            ],
        ];

        $initiateResponse = Http::withHeaders([
            'Authorization'          => "Bearer {$accessToken}",
            'Content-Type'           => 'application/json; charset=UTF-8',
            'X-Upload-Content-Type'  => 'video/*',
            'X-Upload-Content-Length' => strlen($videoBody),
        ])->post(
            'https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status',
            $metadata
        );

        if (!$initiateResponse->successful()) {
            Log::error('YouTube resumable upload initiation failed', ['response' => $initiateResponse->body()]);
            throw new \RuntimeException('YouTube upload initiation failed: ' . $initiateResponse->body());
        }

        $uploadUri = $initiateResponse->header('Location');

        if (empty($uploadUri)) {
            throw new \RuntimeException('YouTube did not return a resumable upload URI.');
        }

        // Step 3: Upload the video binary to the resumable URI
        Log::info('YouTube upload: uploading binary to resumable URI');
        $uploadResponse = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type'  => 'video/*',
        ])->withBody($videoBody, 'video/*')->put($uploadUri);

        if (!$uploadResponse->successful()) {
            Log::error('YouTube binary upload failed', ['response' => $uploadResponse->body()]);
            throw new \RuntimeException('YouTube video upload failed: ' . $uploadResponse->body());
        }

        $uploaded = $uploadResponse->json();
        $videoId  = $uploaded['id'] ?? null;

        if (!$videoId) {
            throw new \RuntimeException('YouTube upload response missing video ID.');
        }

        // Step 4: Optionally set custom thumbnail
        if ($thumbnailUrl && !empty($thumbnailUrl)) {
            try {
                $thumbBody = Http::timeout(60)->get($thumbnailUrl)->body();
                if (!empty($thumbBody)) {
                    Http::withHeaders([
                        'Authorization' => "Bearer {$accessToken}",
                        'Content-Type'  => 'image/jpeg',
                    ])->withBody($thumbBody, 'image/jpeg')
                        ->post("https://www.googleapis.com/upload/youtube/v3/thumbnails/set?videoId={$videoId}&uploadType=media");
                }
            } catch (\Throwable $e) {
                // Thumbnail upload is non-critical; log and continue
                Log::warning('YouTube thumbnail upload failed', ['error' => $e->getMessage()]);
            }
        }

        return [
            'success'  => true,
            'video_id' => $videoId,
            'url'      => "https://youtu.be/{$videoId}",
        ];
    }
}

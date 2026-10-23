<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\VideoUpload;
use App\Services\CloudflareR2Service;
use App\Services\GeminiSeoService;
use App\Services\YouTubeOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VideoUploadController extends Controller
{
    /**
     * Initiate a chunked (multipart) upload to Cloudflare R2.
     */
    public function initiateMultipartUpload(Request $request, CloudflareR2Service $r2Service): JsonResponse
    {
        $user = Auth::user();
        if (!$user || (!$user->hasPermission('publish_social_posts') && !$user->hasPermission('create_tasks') && !$user->isAdmin() && !$user->isSuperAdmin())) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'filename'     => 'required|string|max:255',
            'content_type' => 'required|string',
            'file_size'    => 'required|integer|min:1',
            'task_id'      => 'nullable|exists:tasks,id',
        ]);

        $tenantId = $user->effectiveTenantId() ?? 1;
        $cleanFilename = Str::slug(pathinfo($request->filename, PATHINFO_FILENAME)) . '.' . pathinfo($request->filename, PATHINFO_EXTENSION);
        $uuid = (string) Str::uuid();
        $year = date('Y');
        $month = date('m');
        $key = "videos/{$tenantId}/{$year}/{$month}/{$uuid}/{$cleanFilename}";

        try {
            $multipart = $r2Service->generatePresignedMultipartUpload($key, $request->content_type);

            $videoUpload = VideoUpload::create([
                'tenant_id'         => $tenantId,
                'user_id'           => $user->id,
                'task_id'           => $request->task_id,
                'original_filename' => $request->filename,
                'r2_key'            => $key,
                'file_size'         => $request->file_size,
                'mime_type'         => $request->content_type,
                'status'            => 'uploading',
            ]);

            return response()->json([
                'success'         => true,
                'upload_id'       => $multipart['upload_id'],
                'key'             => $key,
                'video_upload_id' => $videoUpload->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('R2 initiate upload failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to initiate upload: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get a presigned upload URL for a specific chunk/part.
     */
    public function getPartUploadUrl(Request $request, CloudflareR2Service $r2Service): JsonResponse
    {
        $request->validate([
            'key'         => 'required|string',
            'upload_id'   => 'required|string',
            'part_number' => 'required|integer|min:1|max:10000',
        ]);

        try {
            $url = $r2Service->presignPartUpload($request->key, $request->upload_id, (int) $request->part_number);
            return response()->json(['success' => true, 'url' => $url]);
        } catch (\Throwable $e) {
            Log::error('R2 presign part failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to get part upload URL: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Complete the multipart upload after all chunks are uploaded.
     */
    public function completeUpload(Request $request, CloudflareR2Service $r2Service, GeminiSeoService $geminiService): JsonResponse
    {
        $request->validate([
            'key'             => 'required|string',
            'upload_id'       => 'required|string',
            'parts'           => 'required|array|min:1',
            'video_upload_id' => 'required|exists:video_uploads,id',
            'task_id'         => 'nullable|exists:tasks,id',
            'auto_ai_seo'     => 'nullable|boolean',
        ]);

        try {
            $result = $r2Service->completeMultipartUpload($request->key, $request->upload_id, $request->parts);

            $videoUpload = VideoUpload::findOrFail($request->video_upload_id);
            $videoUpload->update([
                'status'  => 'ready',
                'r2_url'  => $result['url'],
                'task_id' => $request->task_id ?? $videoUpload->task_id,
            ]);

            // Auto-generate AI SEO if requested or by default
            $seo = null;
            $titleForSeo = $videoUpload->task?->title ?? pathinfo($videoUpload->original_filename, PATHINFO_FILENAME);
            if (!empty($titleForSeo)) {
                try {
                    $seo = $geminiService->generateVideoSeo($titleForSeo, $videoUpload->task?->description ?? '');
                    $videoUpload->update([
                        'ai_title'       => $seo['seo_title'] ?? $titleForSeo,
                        'ai_description' => $seo['description'] ?? '',
                        'ai_tags'        => $seo['tags'] ?? [],
                        'ai_hashtags'    => $seo['hashtags'] ?? [],
                        'ai_chapters'    => json_encode($seo['chapters'] ?? []),
                    ]);
                } catch (\Throwable $aiEx) {
                    Log::warning('Auto AI SEO generation skipped on complete', ['error' => $aiEx->getMessage()]);
                }
            }

            ActivityLog::record(
                'video_uploaded',
                "Uploaded video \"{$videoUpload->original_filename}\" to Cloudflare R2",
                $videoUpload->task,
                ['r2_url' => $result['url'], 'file_size' => $videoUpload->file_size]
            );

            return response()->json([
                'success'         => true,
                'video_upload_id' => $videoUpload->id,
                'r2_url'          => $result['url'],
                'seo'             => $seo,
            ]);
        } catch (\Throwable $e) {
            Log::error('R2 complete upload failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to complete upload: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload thumbnail directly to Cloudflare R2.
     */
    public function uploadThumbnail(Request $request, CloudflareR2Service $r2Service): JsonResponse
    {
        $request->validate([
            'thumbnail'       => 'required|image|max:10240',
            'video_upload_id' => 'required|exists:video_uploads,id',
        ]);

        $file = $request->file('thumbnail');
        $videoUpload = VideoUpload::findOrFail($request->video_upload_id);
        $tenantId = $videoUpload->tenant_id;
        $uuid = (string) Str::uuid();
        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $key = "thumbnails/{$tenantId}/{$uuid}.{$ext}";

        try {
            $content = file_get_contents($file->getRealPath());
            $r2Service->putObject($key, $content, $file->getMimeType());
            $url = $r2Service->getPublicUrl($key);

            $videoUpload->update([
                'thumbnail_r2_key' => $key,
                'thumbnail_r2_url' => $url,
            ]);

            return response()->json([
                'success'       => true,
                'thumbnail_url' => $url,
            ]);
        } catch (\Throwable $e) {
            Log::error('R2 thumbnail upload failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Thumbnail upload failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * On-demand AI SEO generation using Google Gemini.
     */
    public function generateAiSeo(Request $request, GeminiSeoService $geminiService): JsonResponse
    {
        $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'video_upload_id' => 'nullable|exists:video_uploads,id',
        ]);

        try {
            $seo = $geminiService->generateVideoSeo($request->title, $request->description ?? '');

            if ($request->filled('video_upload_id')) {
                $videoUpload = VideoUpload::find($request->video_upload_id);
                if ($videoUpload) {
                    $videoUpload->update([
                        'ai_title'       => $seo['seo_title'] ?? $request->title,
                        'ai_description' => $seo['description'] ?? '',
                        'ai_tags'        => $seo['tags'] ?? [],
                        'ai_hashtags'    => $seo['hashtags'] ?? [],
                        'ai_chapters'    => json_encode($seo['chapters'] ?? []),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'seo'     => $seo,
            ]);
        } catch (\Throwable $e) {
            Log::error('AI SEO generation controller error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to generate AI SEO: ' . $e->getMessage()], 500);
        }
    }

    /**
     * AI Thumbnail text suggestion.
     */
    public function suggestThumbnailText(Request $request, GeminiSeoService $geminiService): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        try {
            $suggestions = $geminiService->suggestThumbnailText($request->title);
            return response()->json(['success' => true, 'suggestions' => $suggestions]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Publish video from R2 to YouTube Channel using OAuth2.
     */
    public function publishToYoutube(Request $request, YouTubeOAuthService $ytService): JsonResponse
    {
        $user = Auth::user();
        if (!$user || (!$user->hasPermission('publish_social_posts') && !$user->isAdmin() && !$user->isSuperAdmin())) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'video_upload_id' => 'required|exists:video_uploads,id',
            'title'           => 'required|string|max:100',
            'description'     => 'nullable|string',
            'tags'            => 'nullable|array',
            'privacy'         => 'nullable|in:public,unlisted,private',
        ]);

        $videoUpload = VideoUpload::findOrFail($request->video_upload_id);

        if (empty($videoUpload->r2_url)) {
            return response()->json(['error' => 'Video file not found in R2 storage.'], 422);
        }

        $tenant = $user->effectiveTenant();
        if (!$tenant || empty($tenant->youtube_access_token)) {
            return response()->json([
                'success'       => false,
                'error'         => 'YouTube Channel not connected via OAuth2. Please connect your YouTube account.',
                'auth_url'      => route('youtube.oauth'),
                'needs_auth'    => true,
            ], 422);
        }

        // Check if token is expired, refresh if possible
        $accessToken = $tenant->youtube_access_token;
        if ($tenant->youtube_token_expires_at && $tenant->youtube_token_expires_at->isPast()) {
            if (!empty($tenant->youtube_refresh_token)) {
                try {
                    $refreshed = $ytService->refreshAccessToken($tenant->youtube_refresh_token);
                    $accessToken = $refreshed['access_token'];
                    $tenant->update([
                        'youtube_access_token'     => $accessToken,
                        'youtube_token_expires_at' => now()->addSeconds($refreshed['expires_in'] ?? 3600),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('YouTube token refresh failed', ['error' => $e->getMessage()]);
                    return response()->json([
                        'success'    => false,
                        'error'      => 'YouTube authorization has expired. Please re-authenticate your channel.',
                        'auth_url'   => route('youtube.oauth'),
                        'needs_auth' => true,
                    ], 422);
                }
            }
        }

        try {
            $videoUpload->update(['status' => 'processing', 'youtube_status' => 'uploading']);

            $result = $ytService->uploadVideo(
                [
                    'title'        => $request->title,
                    'description'  => $request->description ?? '',
                    'tags'         => $request->tags ?? ($videoUpload->ai_tags ?? []),
                    'privacy'      => $request->privacy ?? 'public',
                    'access_token' => $accessToken,
                ],
                $videoUpload->r2_url,
                $videoUpload->thumbnail_r2_url
            );

            $videoUpload->update([
                'status'           => 'published',
                'youtube_video_id' => $result['video_id'],
                'youtube_url'      => $result['url'],
                'youtube_status'   => 'uploaded',
            ]);

            // Link to task if applicable
            if ($videoUpload->task_id) {
                $task = $videoUpload->task;
                if ($task && empty($task->youtube_url)) {
                    $task->update(['youtube_url' => $result['url']]);
                }
            }

            ActivityLog::record(
                'youtube_published',
                "Published video \"{$request->title}\" directly to YouTube via OAuth2",
                $videoUpload->task,
                ['video_id' => $result['video_id'], 'url' => $result['url']]
            );

            return response()->json([
                'success'     => true,
                'video_id'    => $result['video_id'],
                'youtube_url' => $result['url'],
            ]);
        } catch (\Throwable $e) {
            $videoUpload->update(['status' => 'failed', 'youtube_status' => 'failed']);
            Log::error('YouTube direct upload failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'YouTube upload failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Start Google OAuth2 flow for YouTube.
     */
    public function youtubeOAuthRedirect(YouTubeOAuthService $ytService)
    {
        $user = Auth::user();
        if (!$user || (!$user->hasPermission('manage_settings') && !$user->hasPermission('publish_social_posts') && !$user->isAdmin() && !$user->isSuperAdmin())) {
            abort(403, 'Unauthorized.');
        }

        $tenant = $user->effectiveTenant();
        $authUrl = $ytService->getAuthorizationUrl($tenant->id);

        return redirect()->away($authUrl);
    }

    /**
     * Handle Google OAuth2 callback for YouTube.
     */
    public function youtubeOAuthCallback(Request $request, YouTubeOAuthService $ytService)
    {
        if ($request->has('error')) {
            return redirect()->route('settings.index')
                ->with('error', 'YouTube authorization denied: ' . $request->error);
        }

        $code = $request->input('code');
        if (empty($code)) {
            return redirect()->route('settings.index')
                ->with('error', 'No authorization code received from Google.');
        }

        // Verify state parameter and HMAC signature
        $state = $request->input('state');
        if (!$state) {
            abort(403, 'Missing state parameter.');
        }

        $decoded = json_decode(base64_decode($state), true);
        if (!is_array($decoded) || !isset($decoded['tenant_id'], $decoded['sig'])) {
            abort(403, 'Invalid state payload.');
        }

        $tenantId = (int) $decoded['tenant_id'];
        $expectedSig = hash_hmac('sha256', (string) $tenantId, config('app.key'));
        if (!hash_equals($expectedSig, (string) $decoded['sig'])) {
            abort(403, 'Invalid state signature.');
        }

        $user = Auth::user();
        abort_unless($user && ($user->isSuperAdmin() || $user->effectiveTenantId() === (int) $tenantId), 403, 'Unauthorized tenant access.');

        try {
            $tokens = $ytService->exchangeCodeForTokens($code);

            $tenant = Tenant::findOrFail($tenantId);

            $updateData = [
                'youtube_access_token'     => $tokens['access_token'],
                'youtube_token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
            ];
            if (!empty($tokens['refresh_token'])) {
                $updateData['youtube_refresh_token'] = $tokens['refresh_token'];
            }
            $tenant->update($updateData);

            ActivityLog::record('settings_updated', 'Connected YouTube Channel with OAuth2 authorization');

            return redirect()->route('settings.index')
                ->with('success', '🎉 YouTube Channel successfully connected via OAuth2! You can now publish videos directly.');
        } catch (\Throwable $e) {
            Log::error('YouTube OAuth callback exception', ['error' => $e->getMessage()]);
            return redirect()->route('settings.index')
                ->with('error', 'YouTube connection failed: ' . $e->getMessage());
        }
    }

    /**
     * List uploaded videos for the tenant.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->effectiveTenantId();

        $videos = VideoUpload::where('tenant_id', $tenantId)
            ->with(['user', 'task'])
            ->latest()
            ->paginate(20);

        return response()->json($videos);
    }
}

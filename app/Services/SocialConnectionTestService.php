<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SocialConnectionTestService
{
    /**
     * Required and recommended Facebook permissions with user-friendly descriptions.
     */
    public const FACEBOOK_REQUIRED_PERMISSIONS = [
        'pages_show_list' => [
            'name' => 'pages_show_list',
            'title' => 'Page Discovery & Identification',
            'description' => 'Allows listing and identifying Facebook Pages managed by the account.',
            'category' => 'Discovery',
            'required' => true,
        ],
        'pages_read_engagement' => [
            'name' => 'pages_read_engagement',
            'title' => 'Engagement & Metrics Sync',
            'description' => 'Required to read post views, likes, reactions, and engagement counts.',
            'category' => 'Analytics',
            'required' => true,
        ],
        'pages_manage_posts' => [
            'name' => 'pages_manage_posts',
            'title' => 'Direct Post Publishing',
            'description' => 'Required to create, update, and publish posts to your Facebook Page feed.',
            'category' => 'Publishing',
            'required' => true,
        ],
        'publish_video' => [
            'name' => 'publish_video',
            'title' => 'Direct Video & Reel Uploading',
            'description' => 'Required to upload MP4 video files and publish Facebook Reels directly.',
            'category' => 'Publishing',
            'required' => true,
        ],
        'read_insights' => [
            'name' => 'read_insights',
            'title' => 'Reach & Impressions Analytics',
            'description' => 'Required to read page and post-level reach, unique impressions, and insights.',
            'category' => 'Analytics',
            'required' => false,
        ],
    ];

    /**
     * Test Facebook Page Access Token live against Meta Graph API.
     *
     * @param string|null $token
     * @param Tenant|null $tenant
     * @param string|null $pageId
     * @return array
     */
    public function testFacebookToken(?string $token = null, ?Tenant $tenant = null, ?string $pageId = null): array
    {
        $token = trim((string)($token ?: $tenant?->facebook_page_access_token));

        if (empty($token)) {
            return [
                'success' => false,
                'platform' => 'facebook',
                'status' => 'missing_token',
                'error' => 'No Facebook Page Access Token provided or configured.',
                'troubleshooting' => 'Please generate a Page Access Token in Meta Developer Console or Meta Business Suite and paste it into the field.',
            ];
        }

        try {
            // 1. Fetch Page Identity & Basic Profile
            $meResponse = Http::timeout(12)->get('https://graph.facebook.com/v19.0/me', [
                'fields' => 'id,name,category,link,is_published',
                'access_token' => $token,
            ]);

            if ($meResponse->failed()) {
                return $this->parseFacebookError($meResponse->json('error') ?? [], $meResponse->status());
            }

            $pageData = $meResponse->json();
            $detectedPageId = $pageData['id'] ?? null;
            $detectedPageName = $pageData['name'] ?? 'Facebook Page';
            $category = $pageData['category'] ?? null;
            $link = $pageData['link'] ?? null;
            $isPublished = $pageData['is_published'] ?? true;

            // 2. Query Granted Permissions on this Token
            $grantedPermissions = [];
            $permResponse = Http::timeout(10)->get('https://graph.facebook.com/v19.0/me/permissions', [
                'access_token' => $token,
            ]);

            if ($permResponse->successful()) {
                $rawPerms = $permResponse->json('data') ?? [];
                foreach ($rawPerms as $permItem) {
                    if (($permItem['status'] ?? '') === 'granted' && !empty($permItem['permission'])) {
                        $grantedPermissions[] = $permItem['permission'];
                    }
                }
            }

            // Fallback: If /me/permissions returned empty data or was restricted for this token type,
            // but /me succeeded, we know at least basic page reading works.
            $evaluatedGranted = [];
            $evaluatedMissing = [];

            foreach (self::FACEBOOK_REQUIRED_PERMISSIONS as $permKey => $meta) {
                if (in_array($permKey, $grantedPermissions, true)) {
                    $evaluatedGranted[$permKey] = array_merge($meta, [
                        'granted' => true,
                        'status' => 'granted',
                    ]);
                } else {
                    $evaluatedMissing[$permKey] = array_merge($meta, [
                        'granted' => false,
                        'status' => 'missing',
                        'advice' => $this->getFacebookPermissionAdvice($permKey),
                    ]);
                }
            }

            // Determine publishing & syncing capabilities
            $hasPermsData = !empty($grantedPermissions);
            $canPublishPosts = in_array('pages_manage_posts', $grantedPermissions, true);
            $canPublishReels = in_array('publish_video', $grantedPermissions, true) && $canPublishPosts;
            $canSyncAnalytics = in_array('pages_read_engagement', $grantedPermissions, true) || in_array('read_insights', $grantedPermissions, true);

            // Construct summary
            $missingCount = count($evaluatedMissing);
            if ($missingCount === 0) {
                $status = 'healthy';
                $summary = "All recommended Facebook permissions are granted! Direct publishing and metrics sync are fully operational for '{$detectedPageName}'.";
            } elseif (!$canPublishPosts && !$canSyncAnalytics && $hasPermsData) {
                $status = 'restricted';
                $summary = "Connected to '{$detectedPageName}', but critical permissions (publishing & analytics) are missing.";
            } else {
                $status = 'partial';
                $summary = "Connected to '{$detectedPageName}'. {$missingCount} permission(s) missing for full automation.";
            }

            return [
                'success' => true,
                'platform' => 'facebook',
                'status' => $status,
                'account_name' => $detectedPageName,
                'account_id' => $detectedPageId,
                'category' => $category,
                'link' => $link,
                'is_published' => $isPublished,
                'has_permissions_data' => $hasPermsData,
                'granted_permissions' => $grantedPermissions,
                'granted_details' => $evaluatedGranted,
                'missing_permissions' => $evaluatedMissing,
                'capabilities' => [
                    'can_publish_posts' => $canPublishPosts,
                    'can_publish_reels' => $canPublishReels,
                    'can_sync_analytics' => $canSyncAnalytics,
                ],
                'summary_message' => $summary,
            ];
        } catch (\Exception $e) {
            Log::error('SocialConnectionTestService::testFacebookToken Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'platform' => 'facebook',
                'status' => 'connection_failed',
                'error' => 'Connection timed out or failed while contacting Meta Graph API.',
                'troubleshooting' => 'Check your server internet connectivity or firewall rules for outbound connections to graph.facebook.com.',
            ];
        }
    }

    /**
     * Parse and diagnose Meta Graph API errors with actionable Bengali/English guidance.
     */
    private function parseFacebookError(array $error, int $httpStatus): array
    {
        $code = (int)($error['code'] ?? 0);
        $subcode = (int)($error['error_subcode'] ?? 0);
        $rawMessage = (string)($error['message'] ?? 'Unknown Meta Graph API error');

        $errorTitle = 'Facebook Token Validation Failed';
        $troubleshooting = 'Generate a fresh Page Access Token from Meta Developer Console > Tools > Graph API Explorer.';

        if ($code === 190) {
            if ($subcode === 463) {
                $errorTitle = 'Token Expired (Error 190, Subcode 463)';
                $troubleshooting = 'Your Facebook Page Access Token has expired. In Meta Graph API Explorer, generate a User Token with Page permissions, then exchange it for a long-lived or never-expiring Page Access Token.';
            } elseif ($subcode === 467) {
                $errorTitle = 'Invalid Access Token (Error 190, Subcode 467)';
                $troubleshooting = 'The token format is invalid or corrupted. Ensure you copied the entire token string without extra spaces or truncated characters.';
            } elseif ($subcode === 460) {
                $errorTitle = 'Password Changed (Error 190, Subcode 460)';
                $troubleshooting = 'The Facebook account changed its password, which revoked all active tokens. Please log in and generate a new token.';
            } elseif ($subcode === 458) {
                $errorTitle = 'App Deauthorized (Error 190, Subcode 458)';
                $troubleshooting = 'The Facebook Page deauthorized or removed your Meta App. Re-authorize the application in Page Settings > Business Integrations.';
            } else {
                $errorTitle = 'Invalid or Expired Token (Error 190)';
                $troubleshooting = "Meta rejected this token: '{$rawMessage}'. Please generate a new Page Access Token with full page management scopes.";
            }
        } elseif ($code === 200) {
            $errorTitle = 'Permission Denied (Error 200)';
            $troubleshooting = 'The token does not have permission to view or manage this Facebook Page. Ensure the user who created the token has an Admin or Task role on the Page.';
        } elseif ($code === 4 || $code === 17) {
            $errorTitle = 'Meta Rate Limit Exceeded (Error ' . $code . ')';
            $troubleshooting = 'Too many requests made to Meta Graph API recently. Meta temporarily throttled calls. Please wait 10-15 minutes and retry.';
        } elseif ($code === 100) {
            $errorTitle = 'Invalid Parameter (Error 100)';
            $troubleshooting = 'The request parameter or endpoint was rejected by Meta. Check if the token is a Page token rather than a User or App token.';
        } else {
            $errorTitle = "Meta API Error (HTTP {$httpStatus})";
            $troubleshooting = $rawMessage ?: 'Please verify that your Meta App is published or in Live mode, and that your Page Access Token is active.';
        }

        return [
            'success' => false,
            'platform' => 'facebook',
            'status' => 'error',
            'error' => $errorTitle,
            'error_code' => $code,
            'error_subcode' => $subcode,
            'raw_message' => $rawMessage,
            'troubleshooting' => $troubleshooting,
        ];
    }

    /**
     * Provide specific instructions for missing permissions.
     */
    private function getFacebookPermissionAdvice(string $permission): string
    {
        return match ($permission) {
            'pages_show_list' => "Add 'pages_show_list' permission in Meta Graph API Explorer or your Meta App Dashboard under App Review > Permissions.",
            'pages_read_engagement' => "Add 'pages_read_engagement' permission to read likes, views, and post engagement.",
            'pages_manage_posts' => "Add 'pages_manage_posts' permission so the system can create and publish posts directly to your Page.",
            'publish_video' => "Add 'publish_video' permission so videos and Reels can be uploaded directly to your Facebook Page.",
            'read_insights' => "Add 'read_insights' permission to fetch post reach, impressions, and engagement metrics.",
            default => "Grant the '{$permission}' scope in your Meta App configuration.",
        };
    }

    /**
     * Test YouTube Data API v3 Key live against Google APIs.
     *
     * @param string|null $apiKey
     * @param Tenant|null $tenant
     * @param string|null $channelId
     * @return array
     */
    public function testYoutubeApiKey(?string $apiKey = null, ?Tenant $tenant = null, ?string $channelId = null): array
    {
        $apiKey = trim((string)($apiKey ?: $tenant?->youtube_api_key));

        if (empty($apiKey)) {
            return [
                'success' => false,
                'platform' => 'youtube',
                'status' => 'missing_key',
                'error' => 'No YouTube API Key provided or configured.',
                'troubleshooting' => 'Generate an API Key in Google Cloud Console with YouTube Data API v3 enabled and paste it here.',
            ];
        }

        // Try checking a specific channel if provided or if tenant has one
        $targetChannel = $channelId ?: $tenant?->youtubeChannels()->first()?->name;

        try {
            // 1. If channel ID looks like a standard YouTube channel ID (e.g. UC...)
            if ($targetChannel && preg_match('/^UC[\w-]{21,23}$/', trim($targetChannel))) {
                $channelResponse = Http::timeout(10)->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'snippet,statistics',
                    'id' => trim($targetChannel),
                    'key' => $apiKey,
                ]);

                if ($channelResponse->successful() && !empty($channelResponse->json('items'))) {
                    $channelItem = $channelResponse->json('items')[0];
                    return $this->formatYoutubeSuccess($channelItem, $apiKey);
                }

                // If channel call failed with error, parse the error
                if ($channelResponse->failed()) {
                    return $this->parseYoutubeError($channelResponse->json('error') ?? [], $channelResponse->status());
                }
            }

            // 2. Standard test query (1 quota unit) to verify API key validity, status & quotas
            $testResponse = Http::timeout(10)->get('https://www.googleapis.com/youtube/v3/videos', [
                'part' => 'snippet',
                'chart' => 'mostPopular',
                'maxResults' => 1,
                'key' => $apiKey,
            ]);

            if ($testResponse->failed()) {
                return $this->parseYoutubeError($testResponse->json('error') ?? [], $testResponse->status());
            }

            // API Key is fully valid!
            return [
                'success' => true,
                'platform' => 'youtube',
                'status' => 'healthy',
                'channel_title' => 'YouTube Data API v3 Verified',
                'channel_id' => null,
                'subscriber_count' => null,
                'video_count' => null,
                'view_count' => null,
                'thumbnail' => null,
                'capabilities' => [
                    'can_read_metrics' => true,
                    'can_verify_links' => true,
                    'can_fetch_video_details' => true,
                ],
                'direct_upload_note' => 'YouTube Data API v3 key enables real-time video metrics syncing (views, likes) and URL validation. Note: Direct video file uploads to YouTube require OAuth 2.0 user credentials (youtube.upload scope) rather than an API key.',
                'summary_message' => 'YouTube Data API v3 key is verified and operational! Metrics sync and link verification are ready.',
            ];
        } catch (\Exception $e) {
            Log::error('SocialConnectionTestService::testYoutubeApiKey Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'platform' => 'youtube',
                'status' => 'connection_failed',
                'error' => 'Connection timed out or failed while contacting Google YouTube API.',
                'troubleshooting' => 'Check your server internet connectivity or firewall rules for outbound connections to www.googleapis.com.',
            ];
        }
    }

    /**
     * Format a successful YouTube channel response.
     */
    private function formatYoutubeSuccess(array $channelItem, string $apiKey): array
    {
        $snippet = $channelItem['snippet'] ?? [];
        $stats = $channelItem['statistics'] ?? [];

        $title = $snippet['title'] ?? 'YouTube Channel';
        $subCount = isset($stats['subscriberCount']) ? number_format((int)$stats['subscriberCount']) : null;
        $videoCount = isset($stats['videoCount']) ? number_format((int)$stats['videoCount']) : null;
        $viewCount = isset($stats['viewCount']) ? number_format((int)$stats['viewCount']) : null;
        $thumb = $snippet['thumbnails']['default']['url'] ?? null;

        return [
            'success' => true,
            'platform' => 'youtube',
            'status' => 'healthy',
            'channel_title' => $title,
            'channel_id' => $channelItem['id'] ?? null,
            'subscriber_count' => $subCount,
            'video_count' => $videoCount,
            'view_count' => $viewCount,
            'thumbnail' => $thumb,
            'capabilities' => [
                'can_read_metrics' => true,
                'can_verify_links' => true,
                'can_fetch_video_details' => true,
            ],
            'direct_upload_note' => 'YouTube Data API v3 key enables real-time video metrics syncing (views, likes) and URL validation. Note: Direct video file uploads to YouTube require OAuth 2.0 user credentials (youtube.upload scope) rather than an API key.',
            'summary_message' => "YouTube Data API v3 key is verified and operational for channel '{$title}'!",
        ];
    }

    /**
     * Parse and diagnose Google YouTube API errors.
     */
    private function parseYoutubeError(array $error, int $httpStatus): array
    {
        $code = (int)($error['code'] ?? $httpStatus);
        $rawMessage = (string)($error['message'] ?? 'Unknown Google API error');
        $errors = (array)($error['errors'] ?? []);
        $reason = (string)($errors[0]['reason'] ?? '');

        $errorTitle = 'Invalid YouTube API Key';
        $troubleshooting = 'Google rejected this key. Please check Google Cloud Console > Credentials and copy the correct API Key string.';

        $lowerMsg = strtolower($rawMessage);

        if ($reason === 'keyInvalid' || Str::contains($lowerMsg, ['api key not valid', 'badrequest', 'key not valid', 'invalid argument'])) {
            $errorTitle = 'Invalid YouTube API Key (400 Bad Request)';
            $troubleshooting = 'The YouTube API key was not recognized by Google. Please check Google Cloud Console > Credentials and verify that you copied the complete key without any leading or trailing spaces.';
        } elseif ($reason === 'accessNotConfigured' || Str::contains($lowerMsg, ['accessnotconfigured', 'has not been used in project', 'disabled'])) {
            $errorTitle = 'YouTube Data API v3 is Disabled';
            $troubleshooting = 'YouTube Data API v3 is not enabled in your Google Cloud Project. Go to Google Cloud Console > APIs & Services > Enabled APIs & Services, search for "YouTube Data API v3", and click "Enable".';
        } elseif ($reason === 'quotaExceeded' || $reason === 'dailyLimitExceeded' || Str::contains($lowerMsg, ['quota', 'dailylimitexceeded', 'rate limit'])) {
            $errorTitle = 'YouTube Daily Quota Exceeded';
            $troubleshooting = 'The daily request quota (default 10,000 units/day) for this Google Cloud project has been exceeded. The quota resets at midnight Pacific Time (PST), or you can request an increase in Google Cloud Console.';
        } elseif ($reason === 'ipRefererBlocked' || Str::contains($lowerMsg, ['restricted', 'referer', 'ip address', 'forbidden'])) {
            $errorTitle = 'API Key Restrictions Blocked the Request (403 Forbidden)';
            $troubleshooting = 'Your Google Cloud API Key has Application Restrictions (e.g., restricted to specific HTTP referrers or IP addresses) that prevented this server from making requests. In Google Cloud Console > Credentials, configure the key to allow your server IP or domain, or set restrictions to "None" for testing.';
        } else {
            $errorTitle = "Google YouTube API Error ({$code})";
            $troubleshooting = $rawMessage ?: 'Please verify that YouTube Data API v3 is enabled and that your Google Cloud Project billing/credentials are active.';
        }

        return [
            'success' => false,
            'platform' => 'youtube',
            'status' => 'error',
            'error' => $errorTitle,
            'error_code' => $code,
            'reason' => $reason,
            'raw_message' => $rawMessage,
            'troubleshooting' => $troubleshooting,
        ];
    }
}

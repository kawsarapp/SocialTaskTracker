<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialMediaSyncService
{
    /**
     * Synchronize stats for a single task.
     */
    public function syncTask(Task $task): array
    {
        $tenant = $task->tenant;
        if (!$tenant) {
            return ['success' => false, 'error' => 'No tenant associated with task.'];
        }

        $syncErrors = [];
        $updatedData = [];

        // 1. YouTube Sync
        if ($task->youtube_url && $tenant->youtube_api_key) {
            $youtubeResult = $this->syncYoutube($task, $tenant->youtube_api_key);
            if ($youtubeResult['success']) {
                $updatedData['views'] = $youtubeResult['views'];
                $updatedData['likes'] = $youtubeResult['likes'];
            } else {
                $syncErrors[] = 'YouTube: ' . $youtubeResult['error'];
            }
        }

        // 2. Facebook Sync
        if ($task->facebook_url && $tenant->facebook_page_access_token) {
            $facebookResult = $this->syncFacebook($task, $tenant->facebook_page_access_token);
            if ($facebookResult['success']) {
                // If YouTube already set views/likes, we aggregate them or keep the highest
                $updatedData['views'] = ($updatedData['views'] ?? 0) + $facebookResult['views'];
                $updatedData['likes'] = ($updatedData['likes'] ?? 0) + $facebookResult['likes'];
                $updatedData['reach'] = ($updatedData['reach'] ?? 0) + $facebookResult['reach'];
            } else {
                $syncErrors[] = 'Facebook: ' . $facebookResult['error'];
            }
        }

        // Save outcomes
        if (!empty($updatedData)) {
            $task->update($updatedData);
        }

        if (!empty($syncErrors)) {
            $errorString = implode(' | ', $syncErrors);
            $task->update(['api_sync_error' => $errorString]);
            return ['success' => false, 'error' => $errorString];
        } else {
            $task->update(['api_sync_error' => null]);
            return ['success' => true];
        }
    }

    /**
     * Sync stats for all recent tasks of a tenant.
     */
    public function syncTenantTasks(Tenant $tenant, int $daysLimit = 7): int
    {
        $tasks = Task::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('created_at', '>=', now()->subDays($daysLimit))
            ->where(function ($q) {
                $q->whereNotNull('youtube_url')->orWhereNotNull('facebook_url');
            })
            ->get();

        $successCount = 0;
        foreach ($tasks as $task) {
            $result = $this->syncTask($task);
            if ($result['success']) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * Sync YouTube video statistics.
     */
    private function syncYoutube(Task $task, string $apiKey): array
    {
        $videoId = $this->extractYoutubeId($task->youtube_url);
        if (!$videoId) {
            return ['success' => false, 'error' => 'Could not parse YouTube Video ID.'];
        }

        try {
            $response = Http::timeout(10)->get("https://www.googleapis.com/youtube/v3/videos", [
                'part' => 'statistics',
                'id' => $videoId,
                'key' => $apiKey,
            ]);

            if ($response->failed()) {
                $errorMsg = $response->json('error.message') ?? 'HTTP status ' . $response->status();
                return ['success' => false, 'error' => $errorMsg];
            }

            $data = $response->json();
            if (empty($data['items'])) {
                return ['success' => false, 'error' => 'Video not found (could be private or deleted).'];
            }

            $stats = $data['items'][0]['statistics'] ?? [];
            return [
                'success' => true,
                'views' => (int)($stats['viewCount'] ?? 0),
                'likes' => (int)($stats['likeCount'] ?? 0),
            ];
        } catch (\Exception $e) {
            Log::error('YouTube Sync Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Connection timed out or failed.'];
        }
    }

    /**
     * Sync Facebook video/post statistics.
     */
    private function syncFacebook(Task $task, string $token): array
    {
        $objectId = $this->extractFacebookId($task->facebook_url);
        if (!$objectId) {
            return ['success' => false, 'error' => 'Could not parse Facebook Video/Post ID.'];
        }

        try {
            // Fetch views, likes, and engagement metrics
            $response = Http::timeout(10)->get("https://graph.facebook.com/v19.0/{$objectId}", [
                'fields' => 'views,engagement,likes.summary(true)',
                'access_token' => $token,
            ]);

            if ($response->failed()) {
                $errorMsg = $response->json('error.message') ?? 'HTTP status ' . $response->status();
                return ['success' => false, 'error' => $errorMsg];
            }

            $data = $response->json();
            
            // Extract views
            $views = (int)($data['views'] ?? 0);
            
            // Extract likes
            $likes = (int)($data['likes']['summary']['total_count'] ?? 0);
            if ($likes === 0 && isset($data['engagement']['count'])) {
                $likes = (int)$data['engagement']['count'];
            }

            // Extract reach/impressions (If insights are accessible)
            $reach = 0;
            $insightsResponse = Http::timeout(10)->get("https://graph.facebook.com/v19.0/{$objectId}/insights", [
                'metric' => 'post_impressions_unique',
                'access_token' => $token,
            ]);
            if ($insightsResponse->successful()) {
                $insightsData = $insightsResponse->json();
                $reach = (int)($insightsData['data'][0]['values'][0]['value'] ?? 0);
            }

            return [
                'success' => true,
                'views' => $views,
                'likes' => $likes,
                'reach' => $reach,
            ];
        } catch (\Exception $e) {
            Log::error('Facebook Sync Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Connection timed out or failed.'];
        }
    }

    /**
     * Regex helper to extract YouTube Video ID.
     */
    private function extractYoutubeId(string $url): ?string
    {
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Regex helper to extract Facebook Video or Post ID.
     */
    private function extractFacebookId(string $url): ?string
    {
        // 1. Matches watch/?v=12345
        if (preg_match('/watch\/?\?v=(\d+)/i', $url, $matches)) {
            return $matches[1];
        }
        // 2. Matches facebook.com/permalink.php?story_fbid=123&id=456
        if (preg_match('/story_fbid=(\d+)/i', $url, $matches)) {
            return $matches[1];
        }
        // 3. Matches posts/123 or videos/123 or groups/xyz/permalink/123
        if (preg_match('/\/(?:posts|videos|permalink)\/(\d+)/i', $url, $matches)) {
            return $matches[1];
        }
        // 4. Fallback: match any large numeric block in URL path
        if (preg_match('/\/(\d+)\/?$/i', $url, $matches)) {
            return $matches[1];
        }
        
        // Clean fallback
        $parts = parse_url($url);
        if (isset($parts['path'])) {
            $pathParts = explode('/', trim($parts['path'], '/'));
            foreach (array_reverse($pathParts) as $part) {
                if (is_numeric($part)) {
                    return $part;
                }
            }
        }

        return null;
    }
}

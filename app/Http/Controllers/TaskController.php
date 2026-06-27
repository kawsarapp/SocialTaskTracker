<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\FacebookPage;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\Team;
use App\Models\User;
use App\Models\YoutubeChannel;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks with filters.
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Verify that user belongs to a tenant (except Super Admin who might view globally, but they should select a tenant or be scoped to none)
        $query = Task::with([
            'taskType', 'facebookPage', 'youtubeChannel', 'area', 'team',
            'scriptWriter', 'voiceArtist', 'videoEditor', 'entryUser'
        ]);

        // If Super Admin is viewing the dashboard, they can see all or select a tenant
        if (Auth::user()->isSuperAdmin() && $request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        // Apply Filters
        // 1. Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // 2. Filter by Team
        if ($request->filled('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        // 3. Filter by User (Entry By)
        if ($request->filled('user_id')) {
            $query->where('entry_by', $request->user_id);
        }

        // 4. Filter by Task Type
        if ($request->filled('task_type_id')) {
            $query->where('task_type_id', $request->task_type_id);
        }

        // 5. Filter by Date Range (From - To)
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('on_air_date', [$request->from_date, $request->to_date]);
        }

        // 6. Filter by Empty Links (Quick filters for pending work)
        if ($request->filled('empty_link')) {
            if ($request->empty_link === 'facebook') {
                $query->whereNull('facebook_url')->orWhere('facebook_url', '');
            } elseif ($request->empty_link === 'youtube') {
                $query->whereNull('youtube_url')->orWhere('youtube_url', '');
            } elseif ($request->empty_link === 'both') {
                $query->where(function($q) {
                    $q->where(function($q2) {
                        $q2->whereNull('facebook_url')->orWhere('facebook_url', '');
                    })->where(function($q3) {
                        $q3->whereNull('youtube_url')->orWhere('youtube_url', '');
                    });
                });
            } elseif ($request->empty_link === 'either') {
                $query->where(function($q) {
                    $q->whereNull('facebook_url')
                      ->orWhere('facebook_url', '')
                      ->orWhereNull('youtube_url')
                      ->orWhere('youtube_url', '');
                });
            }
        }

        // 7. Extra Filters (Page, Channel, Tags, Area)
        if ($request->filled('facebook_page_id')) {
            $query->where('facebook_page_id', $request->facebook_page_id);
        }
        if ($request->filled('youtube_channel_id')) {
            $query->where('youtube_channel_id', $request->youtube_channel_id);
        }
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }
        if ($request->filled('tag')) {
            $query->where('tags', 'like', '%' . $request->tag . '%');
        }

        // 8. Scope Filter (All vs My Tasks vs Pending Edits)
        if ($request->input('scope') === 'my_tasks') {
            $query->where(function($q) {
                $userId = Auth::id();
                $q->where('entry_by', $userId)
                  ->orWhere('script_by', $userId)
                  ->orWhere('voice_by', $userId)
                  ->orWhere('video_edit_by', $userId);
            });
        } elseif ($request->input('scope') === 'pending_edit') {
            $query->where('video_edit_by', Auth::id())
                  ->where(function($q) {
                      $q->where(function($q2) {
                          $q2->whereNull('facebook_url')->orWhere('facebook_url', '');
                      })->orWhere(function($q3) {
                          $q3->whereNull('youtube_url')->orWhere('youtube_url', '');
                      });
                  });
        }

        // Sorting & Pagination
        $perPage = $request->input('per_page', 50);
        $tasks = $query->withCount('comments')
                       ->orderBy('on_air_date', 'desc')
                       ->orderBy('on_air_time', 'desc')
                       ->paginate($perPage)
                       ->withQueryString();

        // Get filter dropdown datasets (scoped to tenant)
        $teams = Team::all();
        $taskTypes = TaskType::all();
        $facebookPages = FacebookPage::where('is_active', true)->get();
        $youtubeChannels = YoutubeChannel::where('is_active', true)->get();
        $areas = Area::all();
        $users = User::where('tenant_id', $tenantId)->get();

        return view('tasks.index', compact('tasks', 'teams', 'taskTypes', 'facebookPages', 'youtubeChannels', 'areas', 'users'));
    }

    /**
     * Display tasks on a calendar view.
     */
    public function calendar()
    {
        $tenantId = Auth::user()->tenant_id;

        // Fetch all tenant tasks with relations
        $tasks = Task::with(['taskType', 'area', 'team', 'scriptWriter', 'voiceArtist', 'videoEditor', 'entryUser'])->get();

        // Format events array for FullCalendar
        $events = $tasks->map(function ($task) {
            // Combine on_air_date and on_air_time into a proper ISO datetime if possible
            $startDateTime = $task->on_air_date->format('Y-m-d');
            if ($task->on_air_time) {
                try {
                    $timeStr = date('H:i:s', strtotime($task->on_air_time));
                    $startDateTime = $task->on_air_date->format('Y-m-d') . 'T' . $timeStr;
                } catch (\Exception $e) {
                    // Fallback to date only
                }
            }

            return [
                'id' => $task->id,
                'title' => '[' . ($task->taskType->name ?? 'Task') . '] ' . $task->title,
                'start' => $startDateTime,
                'color' => $task->taskType->color ?? '#4f46e5', // Theme color or task type color
                'extendedProps' => [
                    'title_raw' => $task->title,
                    'task_type' => $task->taskType->name ?? 'Unknown',
                    'task_type_color' => $task->taskType->color ?? '#e2e8f0',
                    'facebook_url' => $task->facebook_url ?? '',
                    'youtube_url' => $task->youtube_url ?? '',
                    'facebook_page' => $task->facebookPage->name ?? '',
                    'youtube_channel' => $task->youtubeChannel->name ?? '',
                    'on_air_time' => $task->on_air_time ?? '',
                    'on_air_date' => $task->on_air_date->format('Y-m-d'),
                    'area' => $task->area->name ?? '',
                    'team' => $task->team->name ?? '',
                    'script_by' => $task->scriptWriter->name ?? 'Not Assigned',
                    'voice_by' => $task->voiceArtist->name ?? 'Not Assigned',
                    'video_edit_by' => $task->videoEditor->name ?? 'Not Assigned',
                    'entry_by' => $task->entryUser->name ?? 'Unknown',
                    'tags' => $task->tags ?? '',
                    'remarks' => $task->remarks ?? '',
                    'edit_url' => route('tasks.edit', $task->id),
                ]
            ];
        });

        return view('tasks.calendar', [
            'eventsJson' => json_encode($events)
        ]);
    }

    /**
     * Show the form for creating a new task.
     */
    public function create()
    {
        $tenantId = Auth::user()->tenant_id;

        $taskTypes = TaskType::all();
        $facebookPages = FacebookPage::where('is_active', true)->get();
        $youtubeChannels = YoutubeChannel::where('is_active', true)->get();
        $areas = Area::all();
        $teams = Team::all();
        $users = User::where('tenant_id', $tenantId)->get(); // All tenant users for dropdown assignments
        $shifts = Shift::all();

        return view('tasks.create', compact('taskTypes', 'facebookPages', 'youtubeChannels', 'areas', 'teams', 'users', 'shifts'));
    }

    /**
     * Store a newly created task in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:500',
            'task_type_id' => 'required|exists:task_types,id',
            'facebook_url' => 'nullable|url|max:2048',
            'facebook_page_id' => 'nullable|exists:facebook_pages,id',
            'youtube_url' => 'nullable|url|max:2048',
            'youtube_channel_id' => 'nullable|exists:youtube_channels,id',
            'on_air_time' => 'required|string|max:50',
            'on_air_date' => 'required|date',
            'area_id' => 'nullable|exists:areas,id',
            'team_id' => 'nullable|exists:teams,id',
            'script_by' => 'nullable|exists:users,id',
            'voice_by' => 'nullable|exists:users,id',
            'video_edit_by' => 'nullable|exists:users,id',
            'tags' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'shift_id' => 'nullable|exists:shifts,id',
        ]);

        Task::create(array_merge($request->all(), [
            'entry_by' => Auth::id(),
            'tenant_id' => Auth::user()->tenant_id,
        ]));

        return redirect()->route('dashboard')->with('success', 'Task logged successfully!');
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit(Task $task)
    {
        Gate::authorize('update', $task);

        // Tenant scope is checked automatically via Global Scope in BelongsToTenant trait
        $tenantId = Auth::user()->tenant_id;

        $taskTypes = TaskType::all();
        $facebookPages = FacebookPage::where('is_active', true)->get();
        $youtubeChannels = YoutubeChannel::where('is_active', true)->get();
        $areas = Area::all();
        $teams = Team::all();
        $users = User::where('tenant_id', $tenantId)->get();
        $shifts = Shift::all();

        return view('tasks.edit', compact('task', 'taskTypes', 'facebookPages', 'youtubeChannels', 'areas', 'teams', 'users', 'shifts'));
    }

    /**
     * Update the specified task in storage.
     */
    public function update(Request $request, Task $task)
    {
        Gate::authorize('update', $task);

        $request->validate([
            'title' => 'required|string|max:500',
            'task_type_id' => 'required|exists:task_types,id',
            'facebook_url' => 'nullable|url|max:2048',
            'facebook_page_id' => 'nullable|exists:facebook_pages,id',
            'youtube_url' => 'nullable|url|max:2048',
            'youtube_channel_id' => 'nullable|exists:youtube_channels,id',
            'on_air_time' => 'required|string|max:50',
            'on_air_date' => 'required|date',
            'area_id' => 'nullable|exists:areas,id',
            'team_id' => 'nullable|exists:teams,id',
            'script_by' => 'nullable|exists:users,id',
            'voice_by' => 'nullable|exists:users,id',
            'video_edit_by' => 'nullable|exists:users,id',
            'tags' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'shift_id' => 'nullable|exists:shifts,id',
        ]);

        $task->update($request->all());

        return redirect()->route('dashboard')->with('success', 'Task updated successfully!');
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(Task $task)
    {
        Gate::authorize('delete', $task);

        $task->delete();
        return redirect()->route('dashboard')->with('success', 'Task deleted successfully!');
    }

    /**
     * Fetch social media video metadata (Title, Thumbnail).
     */
    public function fetchMetadata(Request $request)
    {
        $url = $request->query('url');

        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid URL provided.'
            ], 400);
        }

        try {
            $parsedUrl = parse_url($url);
            $host = $parsedUrl['host'] ?? '';

            // YouTube Fetching
            if (str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be')) {
                // Use YouTube oEmbed endpoint (free, no API key needed, extremely fast and reliable)
                $response = Http::get('https://www.youtube.com/oembed', [
                    'url' => $url,
                    'format' => 'json'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return response()->json([
                        'success' => true,
                        'platform' => 'youtube',
                        'title' => $data['title'] ?? '',
                        'thumbnail_url' => $data['thumbnail_url'] ?? '',
                    ]);
                }
            }

            // Facebook Fetching (Meta tag scraping fallback for free title/image extraction)
            if (str_contains($host, 'facebook.com')) {
                // Fetch the public page content with a standard browser User-Agent
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                ])->get($url);

                if ($response->successful()) {
                    $html = $response->body();
                    $title = '';
                    $thumbnailUrl = '';

                    // Match og:title
                    if (preg_match('/<meta[^>]*property=["\']og:title["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $matches) ||
                        preg_match('/<meta[^>]*content=["\']([^"\']+)["\'][^>]*property=["\']og:title["\']/i', $html, $matches)) {
                        $title = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
                    }

                    // Fallback to title tag if og:title is empty or "noarchive"
                    if (!$title || strtolower($title) === 'noarchive') {
                        if (preg_match('/<title>(.*?)<\/title>/i', $html, $titleMatches)) {
                            $title = html_entity_decode($titleMatches[1], ENT_QUOTES, 'UTF-8');
                        }
                    }

                    // Match og:image (Thumbnail)
                    if (preg_match('/<meta[^>]*property=["\']og:image["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $matches) ||
                        preg_match('/<meta[^>]*content=["\']([^"\']+)["\']/i', $html, $matches)) {
                        $thumbnailUrl = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
                    }

                    // Fallback to twitter:image if og:image is empty or "noarchive"
                    if (!$thumbnailUrl || strtolower($thumbnailUrl) === 'noarchive') {
                        if (preg_match('/<meta[^>]*name=["\']twitter:image["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $twMatches) ||
                            preg_match('/<meta[^>]*content=["\']([^"\']+)["\'][^>]*name=["\']twitter:image["\']/i', $html, $twMatches)) {
                            $thumbnailUrl = html_entity_decode($twMatches[1], ENT_QUOTES, 'UTF-8');
                        }
                    }

                    // Clean up title if it contains Facebook suffixes
                    if ($title) {
                        $title = preg_replace('/\s*\|\s*Facebook$/i', '', $title);
                    }

                    if (($title && strtolower($title) !== 'noarchive') || ($thumbnailUrl && strtolower($thumbnailUrl) !== 'noarchive')) {
                        return response()->json([
                            'success' => true,
                            'platform' => 'facebook',
                            'title' => $title,
                            'thumbnail_url' => $thumbnailUrl,
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Could not fetch metadata for this platform.'
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}

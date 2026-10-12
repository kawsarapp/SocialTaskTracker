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
        if (!Auth::user()->hasPermission('view_tasks')) {
            abort(403, 'Unauthorized access.');
        }

        $tenantId = Auth::user()->effectiveTenantId();

        // Verify that user belongs to a tenant (except Super Admin who might view globally, but they should select a tenant or be scoped to none)
        $query = Task::with([
            'taskType', 'facebookPage', 'youtubeChannel', 'area', 'team',
            'scriptWriter', 'voiceArtist', 'videoEditor', 'entryUser',
            'assignments.designation', 'assignments.user'
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
                $query->where(function ($q) {
                    $q->whereNull('facebook_url')->orWhere('facebook_url', '');
                });
            } elseif ($request->empty_link === 'youtube') {
                $query->where(function ($q) {
                    $q->whereNull('youtube_url')->orWhere('youtube_url', '');
                });
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
                  ->orWhereHas('assignments', function($q2) use ($userId) {
                      $q2->where('user_id', $userId);
                  });
            });
        } elseif ($request->input('scope') === 'pending_edit') {
            $query->whereHas('assignments', function($q) {
                $q->where('user_id', Auth::id())
                  ->whereHas('designation', function($q2) {
                      $q2->where('name', 'Video Editor');
                  });
            })->where(function($q) {
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
        $shifts = Shift::all();
        $users = User::where('tenant_id', $tenantId)->get();
        $activeColumns = \App\Models\Designation::where('is_task_column', true)->get();

        // Dashboard KPI Stats
        $todayStr = now()->toDateString();
        $missingLinksCount = Task::where(function ($q) {
            $q->whereNull('facebook_url')
              ->orWhere('facebook_url', '')
              ->orWhereNull('youtube_url')
              ->orWhere('youtube_url', '');
        })->count();

        $publishedCompleteCount = Task::whereNotNull('facebook_url')
            ->where('facebook_url', '<>', '')
            ->whereNotNull('youtube_url')
            ->where('youtube_url', '<>', '')
            ->count();

        $checkedInToday = $tenantId
            ? \App\Models\Attendance::where('tenant_id', $tenantId)
                ->whereDate('date', $todayStr)
                ->whereNotNull('check_in')
                ->count()
            : 0;

        $dashboardKpis = [
            'total_tasks' => Task::count(),
            'today_tasks' => Task::whereDate('on_air_date', $todayStr)->count(),
            'published_complete' => $publishedCompleteCount,
            'missing_links' => $missingLinksCount,
            'checked_in_today' => $checkedInToday,
            'total_views' => (int) Task::sum('views'),
            'total_engagement' => (int) Task::sum('likes') + (int) Task::sum('reach'),
        ];

        $recentLogs = Auth::user()->hasPermission('view_activity_logs')
            ? \App\Models\ActivityLog::with('user')->latest()->take(25)->get()
            : collect();

        return view('tasks.index', compact(
            'tasks', 'teams', 'taskTypes', 'facebookPages', 'youtubeChannels',
            'areas', 'shifts', 'users', 'activeColumns', 'dashboardKpis', 'recentLogs'
        ));
    }

    /**
     * Display tasks on a calendar view.
     */
    public function calendar()
    {
        if (!Auth::user()->hasPermission('view_calendar') && !Auth::user()->hasPermission('view_tasks')) {
            abort(403, 'Unauthorized access.');
        }

        $tenantId = Auth::user()->effectiveTenantId();

        // Fetch all tenant tasks with relations
        $tasks = Task::with([
            'taskType', 'facebookPage', 'youtubeChannel', 'area', 'team',
            'scriptWriter', 'voiceArtist', 'videoEditor', 'entryUser',
            'assignments.designation', 'assignments.user'
        ])->get();

        // Format events array for FullCalendar
        $events = $tasks->map(function ($task) {
            $dateStr = $task->on_air_date?->format('Y-m-d') ?? now()->toDateString();
            $startDateTime = $dateStr;
            if ($task->on_air_time) {
                try {
                    $timeStr = date('H:i:s', strtotime($task->on_air_time));
                    $startDateTime = $dateStr . 'T' . $timeStr;
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
                    'on_air_date' => $dateStr,
                    'area' => $task->area->name ?? '',
                    'team' => $task->team->name ?? '',
                    'script_by' => $task->scriptWriter->name ?? 'Not Assigned',
                    'voice_by' => $task->voiceArtist->name ?? 'Not Assigned',
                    'video_edit_by' => $task->videoEditor->name ?? 'Not Assigned',
                    'entry_by' => $task->entryUser->name ?? 'Unknown',
                    'tags' => $task->tags ?? '',
                    'remarks' => $task->remarks ?? '',
                    'edit_url' => route('tasks.edit', $task->id),
                    'can_edit' => Gate::allows('update', $task),
                    'assignments' => $task->assignments->map(fn($a) => [
                        'role' => $a->designation->name ?? 'Role',
                        'name' => $a->user->name ?? 'N/A',
                    ])->values(),
                    'custom_fields' => $task->custom_fields ?? [],
                ]
            ];
        });

        $activeColumns = \App\Models\Designation::where('is_task_column', true)->get();

        return view('tasks.calendar', [
            'eventsJson' => json_encode($events),
            'activeColumns' => $activeColumns,
        ]);
    }

    /**
     * Show the form for creating a new task.
     */
    public function create()
    {
        if (!Auth::user()->hasPermission('create_tasks')) {
            abort(403, 'Unauthorized access.');
        }

        $tenantId = Auth::user()->effectiveTenantId();

        $taskTypes = TaskType::all();
        $facebookPages = FacebookPage::where('is_active', true)->get();
        $youtubeChannels = YoutubeChannel::where('is_active', true)->get();
        $areas = Area::all();
        $teams = Team::all();
        $users = User::where('tenant_id', $tenantId)->get();
        $shifts = Shift::all();

        // Dynamic Designation Filtering
        $activeColumns = \App\Models\Designation::where('is_task_column', true)->get();
        $usersByDesignation = [];
        foreach ($activeColumns as $col) {
            $candidates = User::where('tenant_id', $tenantId)
                ->where('designation_id', $col->id)
                ->get();
            if ($candidates->isEmpty()) {
                $candidates = $users;
            }
            $usersByDesignation[$col->id] = $candidates;
        }

        return view('tasks.create', compact(
            'taskTypes', 'facebookPages', 'youtubeChannels', 'areas', 'teams', 'users', 'shifts',
            'activeColumns', 'usersByDesignation'
        ));
    }

    /**
     * Store a newly created task in storage.
     */
    public function store(Request $request)
    {
        if (!Auth::user()->hasPermission('create_tasks')) {
            abort(403, 'Unauthorized access.');
        }

        $tenantConfig = Auth::user()->tenant->task_form_config ?? [];

        $rules = [
            'title' => 'required|string|max:500',
            'tags' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'script_file' => 'nullable|file|mimes:docx,pdf,txt,doc|max:5120',
            'staff_assignments' => 'nullable|array',
            'staff_assignments.*' => 'nullable|exists:users,id',
        ];

        $fieldsToMap = [
            'task_type_id' => 'exists:task_types,id',
            'facebook_url' => 'url|max:2048',
            'facebook_page_id' => 'exists:facebook_pages,id',
            'youtube_url' => 'url|max:2048',
            'youtube_channel_id' => 'exists:youtube_channels,id',
            'on_air_time' => 'string|max:50',
            'on_air_date' => 'date',
            'area_id' => 'exists:areas,id',
            'team_id' => 'exists:teams,id',
            'shift_id' => 'exists:shifts,id',
        ];

        foreach ($fieldsToMap as $field => $baseRule) {
            $visible = $tenantConfig[$field]['visible'] ?? true;
            $required = $tenantConfig[$field]['required'] ?? false;

            if ($visible && $required) {
                $rules[$field] = 'required|' . $baseRule;
            } else {
                $rules[$field] = 'nullable|' . $baseRule;
            }
        }

        $fileFields = ['thumbnail', 'script_file'];
        foreach ($fileFields as $field) {
            $visible = $tenantConfig[$field]['visible'] ?? true;
            $required = $tenantConfig[$field]['required'] ?? false;
            if ($visible && $required) {
                $rules[$field] = str_replace('nullable', 'required', $rules[$field]);
            }
        }

        // Custom fields validation
        foreach ($tenantConfig as $key => $fieldVal) {
            if ($fieldVal['is_custom'] ?? false) {
                $visible = $fieldVal['visible'] ?? true;
                $required = $fieldVal['required'] ?? false;

                if ($visible && $required) {
                    $rules["custom_fields.{$key}"] = 'required';
                } else {
                    $rules["custom_fields.{$key}"] = 'nullable';
                }
            }
        }

        $request->validate($rules);

        $data = $request->only([
            'title',
            'task_type_id',
            'facebook_url',
            'facebook_page_id',
            'youtube_url',
            'youtube_channel_id',
            'on_air_time',
            'on_air_date',
            'area_id',
            'team_id',
            'tags',
            'remarks',
            'shift_id',
            'custom_fields',
        ]);

        $data['facebook_page_id'] = $request->input('facebook_page_id') ?: null;
        $data['youtube_channel_id'] = $request->input('youtube_channel_id') ?: null;

        if (empty($data['on_air_date'])) {
            $data['on_air_date'] = now()->toDateString();
        }
        if (empty($data['on_air_time'])) {
            $data['on_air_time'] = now()->format('H:i');
        }
        if (empty($data['task_type_id'])) {
            $data['task_type_id'] = TaskType::first()?->id ?? TaskType::create(['name' => 'General', 'color' => '#3490dc'])->id;
        }

        $tenantId = Auth::user()->effectiveTenantId();

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail_path'] = $request->file('thumbnail')->store('tenant_' . $tenantId . '/tasks', 'public');
        }

        if ($request->hasFile('script_file')) {
            $data['script_path'] = $request->file('script_file')->store('tenant_' . $tenantId . '/tasks', 'public');
        }

        $task = Task::create(array_merge($data, [
            'entry_by' => Auth::id(),
            'tenant_id' => $tenantId,
        ]));

        // Create staff assignments
        if ($request->filled('staff_assignments')) {
            foreach ($request->staff_assignments as $designationId => $userId) {
                if ($userId) {
                    \App\Models\TaskAssignment::create([
                        'task_id' => $task->id,
                        'designation_id' => $designationId,
                        'user_id' => $userId,
                    ]);
                }
            }
        }

        // Sync stats if API credentials are set
        $syncService = new \App\Services\SocialMediaSyncService();
        $syncService->syncTask($task);

        \App\Services\NotificationService::sendTaskNotification($task, 'created');

        return redirect()->route('dashboard')->with('success', 'Task logged successfully!');
    }

    /**
     * Display the specified task.
     */
    public function show(Task $task)
    {
        if (Gate::allows('update', $task)) {
            return redirect()->route('tasks.edit', $task);
        }

        return redirect()->route('tasks.index');
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit(Task $task)
    {
        Gate::authorize('update', $task);

        // Tenant scope is checked automatically via Global Scope in BelongsToTenant trait
        $tenantId = Auth::user()->effectiveTenantId();

        $taskTypes = TaskType::all();
        $facebookPages = FacebookPage::where('is_active', true)->get();
        $youtubeChannels = YoutubeChannel::where('is_active', true)->get();
        $areas = Area::all();
        $teams = Team::all();
        $users = User::where('tenant_id', $tenantId)->get();
        $shifts = Shift::all();

        // Dynamic Designation Filtering
        $activeColumns = \App\Models\Designation::where('is_task_column', true)->get();
        $usersByDesignation = [];
        foreach ($activeColumns as $col) {
            $candidates = User::where('tenant_id', $tenantId)
                ->where('designation_id', $col->id)
                ->get();
            if ($candidates->isEmpty()) {
                $candidates = $users;
            }
            $usersByDesignation[$col->id] = $candidates;
        }

        $assignments = $task->assignments->pluck('user_id', 'designation_id')->toArray();

        return view('tasks.edit', compact(
            'task', 'taskTypes', 'facebookPages', 'youtubeChannels', 'areas', 'teams', 'users', 'shifts',
            'activeColumns', 'usersByDesignation', 'assignments'
        ));
    }

    /**
     * Update the specified task in storage.
     */
    public function update(Request $request, Task $task)
    {
        Gate::authorize('update', $task);

        $tenantConfig = Auth::user()->tenant->task_form_config ?? [];

        $rules = [
            'title' => 'required|string|max:500',
            'tags' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'script_file' => 'nullable|file|mimes:docx,pdf,txt,doc|max:5120',
            'staff_assignments' => 'nullable|array',
            'staff_assignments.*' => 'nullable|exists:users,id',
        ];

        $fieldsToMap = [
            'task_type_id' => 'exists:task_types,id',
            'facebook_url' => 'url|max:2048',
            'facebook_page_id' => 'exists:facebook_pages,id',
            'youtube_url' => 'url|max:2048',
            'youtube_channel_id' => 'exists:youtube_channels,id',
            'on_air_time' => 'string|max:50',
            'on_air_date' => 'date',
            'area_id' => 'exists:areas,id',
            'team_id' => 'exists:teams,id',
            'shift_id' => 'exists:shifts,id',
        ];

        foreach ($fieldsToMap as $field => $baseRule) {
            $visible = $tenantConfig[$field]['visible'] ?? true;
            $required = $tenantConfig[$field]['required'] ?? false;

            if ($visible && $required) {
                $rules[$field] = 'required|' . $baseRule;
            } else {
                $rules[$field] = 'nullable|' . $baseRule;
            }
        }

        $fileFields = ['thumbnail', 'script_file'];
        foreach ($fileFields as $field) {
            $visible = $tenantConfig[$field]['visible'] ?? true;
            $required = $tenantConfig[$field]['required'] ?? false;
            if ($visible && $required) {
                $rules[$field] = str_replace('nullable', 'required', $rules[$field]);
            }
        }

        // Custom fields validation
        foreach ($tenantConfig as $key => $fieldVal) {
            if ($fieldVal['is_custom'] ?? false) {
                $visible = $fieldVal['visible'] ?? true;
                $required = $fieldVal['required'] ?? false;

                if ($visible && $required) {
                    $rules["custom_fields.{$key}"] = 'required';
                } else {
                    $rules["custom_fields.{$key}"] = 'nullable';
                }
            }
        }

        $request->validate($rules);

        $wasMissingLinks = empty($task->facebook_url) && empty($task->youtube_url);

        $data = $request->only([
            'title',
            'task_type_id',
            'facebook_url',
            'facebook_page_id',
            'youtube_url',
            'youtube_channel_id',
            'on_air_time',
            'on_air_date',
            'area_id',
            'team_id',
            'tags',
            'remarks',
            'shift_id',
            'custom_fields',
        ]);

        $data['facebook_page_id'] = $request->input('facebook_page_id') ?: null;
        $data['youtube_channel_id'] = $request->input('youtube_channel_id') ?: null;

        if (empty($data['on_air_date'])) {
            $data['on_air_date'] = $task->on_air_date?->format('Y-m-d') ?? now()->toDateString();
        }
        if (empty($data['on_air_time'])) {
            $data['on_air_time'] = $task->on_air_time ?? now()->format('H:i');
        }
        if (empty($data['task_type_id'])) {
            $data['task_type_id'] = $task->task_type_id ?? TaskType::first()?->id;
        }

        $tenantId = Auth::user()->effectiveTenantId();

        if ($request->hasFile('thumbnail')) {
            if ($task->thumbnail_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($task->thumbnail_path);
            }
            $data['thumbnail_path'] = $request->file('thumbnail')->store('tenant_' . $tenantId . '/tasks', 'public');
        }

        if ($request->hasFile('script_file')) {
            if ($task->script_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($task->script_path);
            }
            $data['script_path'] = $request->file('script_file')->store('tenant_' . $tenantId . '/tasks', 'public');
        }

        $task->update($data);

        // Sync staff assignments
        if ($request->has('staff_assignments')) {
            $task->assignments()->delete();
            foreach ($request->staff_assignments as $designationId => $userId) {
                if ($userId) {
                    \App\Models\TaskAssignment::create([
                        'task_id' => $task->id,
                        'designation_id' => $designationId,
                        'user_id' => $userId,
                    ]);
                }
            }
        }

        // Sync stats if API credentials are set
        $syncService = new \App\Services\SocialMediaSyncService();
        $syncService->syncTask($task);

        $nowHasLinks = !empty($task->facebook_url) || !empty($task->youtube_url);
        if ($wasMissingLinks && $nowHasLinks) {
            \App\Services\NotificationService::sendTaskNotification($task, 'published');
        }

        return redirect()->route('dashboard')->with('success', 'Task updated successfully!');
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(Task $task)
    {
        Gate::authorize('delete', $task);

        // Delete associated files from local public disk
        if ($task->thumbnail_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($task->thumbnail_path);
        }
        if ($task->script_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($task->script_path);
        }

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

    /**
     * Instantly sync views and likes stats for a single task.
     */
    public function syncSingleTask(Task $task)
    {
        if (!Auth::user()->hasPermission('sync_social_metrics') && !Auth::user()->hasPermission('manage_settings') && !Gate::allows('update', $task)) {
            abort(403, 'Unauthorized access.');
        }

        $tenant = Auth::user()->tenant;
        if (!$tenant) {
            return redirect()->back()->with('error', 'No tenant associated with your account.');
        }

        if (!$tenant->youtube_api_key && !$tenant->facebook_page_access_token) {
            return redirect()->back()->with('error', 'Please configure your API keys in settings first.');
        }

        $syncService = new \App\Services\SocialMediaSyncService();
        $result = $syncService->syncTask($task);

        if ($result['success']) {
            return redirect()->back()->with('success', 'Social media stats updated successfully!');
        } else {
            return redirect()->back()->with('error', 'Sync encountered issues: ' . $result['error']);
        }
    }

    /**
     * Inline Quick-Link Update endpoint from the Dashboard.
     */
    public function quickUpdateLinks(Request $request, Task $task)
    {
        if (!Auth::user()->hasPermission('quick_update_links') && !Gate::allows('update', $task)) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'facebook_url' => 'nullable|url|max:500',
            'youtube_url' => 'nullable|url|max:500',
            'facebook_page_id' => 'nullable|exists:facebook_pages,id',
            'youtube_channel_id' => 'nullable|exists:youtube_channels,id',
        ]);

        $wasMissingLinks = empty($task->facebook_url) && empty($task->youtube_url);

        $changes = [];
        $fields = [
            'facebook_url' => $request->input('facebook_url') ?: null,
            'youtube_url' => $request->input('youtube_url') ?: null,
            'facebook_page_id' => $request->has('facebook_page_id') ? ($request->input('facebook_page_id') ?: null) : $task->facebook_page_id,
            'youtube_channel_id' => $request->has('youtube_channel_id') ? ($request->input('youtube_channel_id') ?: null) : $task->youtube_channel_id,
        ];

        foreach ($fields as $key => $newVal) {
            $oldVal = $task->{$key};
            if ((string) $oldVal !== (string) $newVal) {
                $changes[$key] = [
                    'old' => $oldVal,
                    'new' => $newVal,
                ];
                $task->{$key} = $newVal;
            }
        }

        if (!empty($changes)) {
            $task->saveQuietly();
            \App\Models\ActivityLog::record('link_updated', $task->title, $task, $changes);

            $syncService = new \App\Services\SocialMediaSyncService();
            $syncService->syncTask($task);

            $nowHasLinks = !empty($task->facebook_url) || !empty($task->youtube_url);
            if ($wasMissingLinks && $nowHasLinks) {
                \App\Services\NotificationService::sendTaskNotification($task, 'published');
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Links updated successfully!',
                'task' => $task->fresh(['facebookPage', 'youtubeChannel']),
            ]);
        }

        return redirect()->back()->with('success', 'Links updated successfully!');
    }

    /**
     * Bulk Task Actions endpoint (Delete, Assign Team, Assign Shift, Assign Task Type).
     */
    public function bulkAction(Request $request)
    {
        if (!Auth::user()->hasPermission('bulk_actions')) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'task_ids' => 'required|array|min:1',
            'task_ids.*' => 'integer',
            'bulk_type' => 'required|string|in:delete,assign_team,assign_shift,assign_task_type',
            'target_id' => 'nullable|integer',
        ]);

        $tasks = Task::whereIn('id', $request->task_ids)->get();
        if ($tasks->isEmpty()) {
            return redirect()->back()->with('error', 'No valid tasks selected.');
        }

        $count = $tasks->count();
        $bulkType = $request->bulk_type;

        if ($bulkType === 'delete') {
            if (!Auth::user()->hasPermission('delete_tasks')) {
                abort(403, 'You do not have permission to delete tasks.');
            }
            foreach ($tasks as $task) {
                if ($task->thumbnail_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($task->thumbnail_path);
                }
                if ($task->script_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($task->script_path);
                }
                $task->deleteQuietly();
            }
            \App\Models\ActivityLog::record('bulk_deleted', "Bulk deleted {$count} task(s)");
        } elseif ($bulkType === 'assign_team') {
            $team = $request->target_id ? Team::find($request->target_id) : null;
            Task::whereIn('id', $tasks->pluck('id'))->update(['team_id' => $team?->id]);
            $teamName = $team?->name ?? 'Unassigned';
            \App\Models\ActivityLog::record('bulk_updated', "Bulk assigned Team '{$teamName}' to {$count} task(s)");
        } elseif ($bulkType === 'assign_shift') {
            $shift = $request->target_id ? Shift::find($request->target_id) : null;
            Task::whereIn('id', $tasks->pluck('id'))->update(['shift_id' => $shift?->id]);
            $shiftName = $shift?->name ?? 'Unassigned';
            \App\Models\ActivityLog::record('bulk_updated', "Bulk assigned Shift '{$shiftName}' to {$count} task(s)");
        } elseif ($bulkType === 'assign_task_type') {
            $taskType = $request->target_id ? TaskType::find($request->target_id) : null;
            if ($taskType) {
                Task::whereIn('id', $tasks->pluck('id'))->update(['task_type_id' => $taskType->id]);
                \App\Models\ActivityLog::record('bulk_updated', "Bulk assigned Task Type '{$taskType->name}' to {$count} task(s)");
            }
        }

        return redirect()->back()->with('success', "Bulk action applied to {$count} task(s)!");
    }

    /**
     * Export filtered tasks to CSV format for Excel/reporting.
     */
    public function exportCsv(Request $request)
    {
        if (!Auth::user()->hasPermission('export_tasks')) {
            abort(403, 'Unauthorized access.');
        }

        $tenantId = Auth::user()->effectiveTenantId();

        $query = Task::with([
            'taskType', 'facebookPage', 'youtubeChannel', 'area', 'team',
            'entryUser', 'assignments.designation', 'assignments.user'
        ]);

        if (Auth::user()->isSuperAdmin() && $request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('team_id')) {
            $query->where('team_id', $request->team_id);
        }
        if ($request->filled('user_id')) {
            $query->where('entry_by', $request->user_id);
        }
        if ($request->filled('task_type_id')) {
            $query->where('task_type_id', $request->task_type_id);
        }
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('on_air_date', [$request->from_date, $request->to_date]);
        }
        if ($request->filled('empty_link')) {
            if ($request->empty_link === 'facebook') {
                $query->where(function ($q) {
                    $q->whereNull('facebook_url')->orWhere('facebook_url', '');
                });
            } elseif ($request->empty_link === 'youtube') {
                $query->where(function ($q) {
                    $q->whereNull('youtube_url')->orWhere('youtube_url', '');
                });
            } elseif ($request->empty_link === 'both') {
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->whereNull('facebook_url')->orWhere('facebook_url', '');
                    })->where(function ($q3) {
                        $q3->whereNull('youtube_url')->orWhere('youtube_url', '');
                    });
                });
            } elseif ($request->empty_link === 'either') {
                $query->where(function ($q) {
                    $q->whereNull('facebook_url')->orWhere('facebook_url', '')
                      ->orWhereNull('youtube_url')->orWhere('youtube_url', '');
                });
            }
        }

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

        if ($request->input('scope') === 'my_tasks') {
            $query->where(function ($q) {
                $userId = Auth::id();
                $q->where('entry_by', $userId)
                  ->orWhereHas('assignments', function ($q2) use ($userId) {
                      $q2->where('user_id', $userId);
                  });
            });
        } elseif ($request->input('scope') === 'pending_edit') {
            $query->whereHas('assignments', function ($q) {
                $q->where('user_id', Auth::id())
                  ->whereHas('designation', function ($q2) {
                      $q2->where('name', 'Video Editor');
                  });
            })->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNull('facebook_url')->orWhere('facebook_url', '');
                })->orWhere(function ($q3) {
                    $q3->whereNull('youtube_url')->orWhere('youtube_url', '');
                });
            });
        }

        $tasks = $query->orderBy('on_air_date', 'desc')->orderBy('on_air_time', 'desc')->get();

        \App\Models\ActivityLog::record('exported_csv', 'Exported ' . $tasks->count() . ' task(s) to CSV report');

        $fileName = 'tasks_report_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($tasks) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, [
                'ID', 'Title', 'Type', 'On Air Date', 'On Air Time', 
                'Facebook Page', 'Facebook Link', 'YouTube Channel', 'YouTube Link', 
                'News Area / Source', 'Team', 'Entry By', 'Assigned Staff', 'Remarks'
            ]);

            foreach ($tasks as $task) {
                $assignedStaff = [];
                foreach ($task->assignments as $a) {
                    if ($a->user && $a->designation) {
                        $assignedStaff[] = $a->designation->name . ': ' . $a->user->name;
                    }
                }

                fputcsv($file, [
                    $task->id,
                    $task->title,
                    $task->taskType->name ?? 'N/A',
                    $task->on_air_date ? \Carbon\Carbon::parse($task->on_air_date)->format('Y-m-d') : 'N/A',
                    $task->on_air_time ?? 'N/A',
                    $task->facebookPage->name ?? 'N/A',
                    $task->facebook_url ?? 'Missing Link',
                    $task->youtubeChannel->name ?? 'N/A',
                    $task->youtube_url ?? 'Missing Link',
                    $task->area->name ?? 'N/A',
                    $task->team->name ?? 'N/A',
                    $task->entryUser->name ?? 'N/A',
                    implode(', ', $assignedStaff),
                    $task->remarks ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

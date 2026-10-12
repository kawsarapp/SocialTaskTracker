<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Social Task Tracker') }}
            </h2>
            <div class="flex items-center gap-2.5">
                @if(Auth::user()->hasPermission('view_activity_logs'))
                    <button type="button" @click="$dispatch('open-activity-drawer')" class="inline-flex items-center gap-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-gray-200 px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold shadow-sm transition duration-150">
                        <span>🕒</span>
                        <span>Activity Logs</span>
                    </button>
                @endif
                @if(Auth::user()->hasPermission('create_tasks'))
                    <a href="{{ route('tasks.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold shadow transition duration-150">
                        + Create New Task
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    @php
        $formConfig = Auth::user()->tenant->task_form_config ?? [];
        $customFieldsMeta = collect($formConfig)->filter(fn($f) => $f['is_custom'] ?? false)->toArray();

        $tasksForJs = $tasks->getCollection()->mapWithKeys(function($task) {
            return [
                $task->id => [
                    'id'               => $task->id,
                    'title'            => $task->title,
                    'taskType'         => $task->taskType?->name ?? 'N/A',
                    'taskColor'        => $task->taskType?->color ?? '#e2e8f0',
                    'entryUser'        => $task->entryUser?->name ?? 'System',
                    'date'             => $task->on_air_date?->format('d M, Y') ?? 'N/A',
                    'time'             => $task->on_air_time ?? '-',
                    'area'             => $task->area?->name ?? 'General',
                    'team'             => $task->team?->name ?? 'General',
                    'assignments'      => $task->assignments->map(fn($a) => [
                        'role' => $a->designation?->name ?? 'Staff',
                        'name' => $a->user?->name ?? 'Unassigned',
                    ])->values()->toArray(),
                    'tags'             => $task->tags ?? '',
                    'remarks'          => $task->remarks ?? '',
                    'facebookPageId'   => $task->facebook_page_id ?? '',
                    'facebookPage'     => $task->facebookPage?->name ?? '',
                    'facebookUrl'      => $task->facebook_url ?? '',
                    'youtubeChannelId' => $task->youtube_channel_id ?? '',
                    'youtubeChannel'   => $task->youtubeChannel?->name ?? '',
                    'youtubeUrl'       => $task->youtube_url ?? '',
                    'thumbnail'        => $task->thumbnail_path ? asset('storage/' . $task->thumbnail_path) : '',
                    'script'           => $task->script_path ? asset('storage/' . $task->script_path) : '',
                    'shift'            => $task->shift?->name ?? 'General',
                    'editUrl'          => route('tasks.edit', $task->id),
                    'canEdit'          => Auth::user()->can('update', $task),
                    'views'            => number_format((int) $task->views),
                    'likes'            => number_format((int) $task->likes),
                    'reach'            => number_format((int) $task->reach),
                    'apiSyncError'     => $task->api_sync_error ?? '',
                    'customFields'     => $task->custom_fields ?? [],
                ]
            ];
        });

        $pageTaskIds = $tasks->pluck('id')->map(fn($id) => (string) $id)->values()->toArray();
        $canBulkActions = Auth::user()->hasPermission('bulk_actions');
        $canQuickUpdateLinks = Auth::user()->hasPermission('quick_update_links');
    @endphp

    <script>
        window.__tasksMap = @json($tasksForJs);
    </script>

    <div class="py-6" 
         x-data="{ 
            showMoreFilters: {{ request()->anyFilled(['facebook_page_id', 'youtube_channel_id', 'area_id', 'tag', 'empty_link']) ? 'true' : 'false' }}, 
            showModal: false, 
            activeTask: null,
            showQuickLinkModal: false,
            quickLinkTask: null,
            showActivityDrawer: false,
            selectedTaskIds: [],
            allPageTaskIds: {{ json_encode($pageTaskIds) }},
            bulkType: 'assign_team',
            customFieldsMeta: {{ json_encode($customFieldsMeta) }},
            openTaskModal(id) {
                if (window.__tasksMap && window.__tasksMap[id]) {
                    this.activeTask = window.__tasksMap[id];
                    this.showModal = true;
                }
            },
            openQuickLinkModal(id) {
                if (window.__tasksMap && window.__tasksMap[id]) {
                    this.quickLinkTask = Object.assign({}, window.__tasksMap[id]);
                    this.showQuickLinkModal = true;
                }
            },
            toggleSelectAll(event) {
                if (event.target.checked) {
                    this.selectedTaskIds = [...this.allPageTaskIds];
                } else {
                    this.selectedTaskIds = [];
                }
            }
         }"
         @open-activity-drawer.window="showActivityDrawer = true">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Success Toast -->
            @if(session('success'))
                <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center shadow-sm">
                    <svg class="w-5 h-5 me-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Error Toast -->
            @if(session('error'))
                <div class="mb-4 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-center shadow-sm">
                    <svg class="w-5 h-5 me-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span class="text-sm font-medium">{{ session('error') }}</span>
                </div>
            @endif

            <!-- 1. SaaS KPI Summary Strip -->
            @php
                $totalTasksKpi = $dashboardKpis['total_tasks'] ?? 0;
                $publishedCompleteKpi = $dashboardKpis['published_complete'] ?? 0;
                $completionPercent = round(($publishedCompleteKpi / max($totalTasksKpi, 1)) * 100);
            @endphp
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
                <!-- Total Tasks -->
                <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Total Tasks</span>
                        <span class="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl font-extrabold text-gray-900">{{ number_format($totalTasksKpi) }}</p>
                        <p class="text-[11px] font-semibold text-indigo-600 mt-0.5">Today On-Air: {{ number_format($dashboardKpis['today_tasks'] ?? 0) }}</p>
                    </div>
                </div>

                <!-- Published Complete -->
                <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Published Complete</span>
                        <span class="text-xs font-extrabold text-emerald-600">{{ $completionPercent }}%</span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl font-extrabold text-emerald-600">{{ number_format($publishedCompleteKpi) }}</p>
                        <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden mt-1.5">
                            <div class="bg-emerald-500 h-full rounded-full transition-all duration-300" style="width: {{ min($completionPercent, 100) }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Missing Links -->
                <a href="{{ route('tasks.index', array_merge(request()->except('page'), ['empty_link' => 'both'])) }}" 
                   class="bg-white hover:bg-amber-50/40 p-4 rounded-xl border border-gray-100 hover:border-amber-200 shadow-sm flex flex-col justify-between transition group">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400 group-hover:text-amber-700">Missing Links</span>
                        <span class="p-1.5 bg-amber-50 text-amber-600 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl font-extrabold text-amber-600">{{ number_format($dashboardKpis['missing_links'] ?? ($missingLinksCount ?? 0)) }}</p>
                        <p class="text-[11px] font-semibold text-amber-700 mt-0.5 group-hover:underline">Click to filter missing &rarr;</p>
                    </div>
                </a>

                <!-- Staff Checked-In Today -->
                <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Checked-In Today</span>
                        <span class="p-1.5 bg-purple-50 text-purple-600 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl font-extrabold text-purple-600">{{ number_format($dashboardKpis['checked_in_today'] ?? 0) }}</p>
                        <p class="text-[11px] font-semibold text-gray-400 mt-0.5">Active staff on duty today</p>
                    </div>
                </div>

                <!-- Social Views & Reach -->
                <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex flex-col justify-between col-span-2 sm:col-span-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Views & Reach</span>
                        <span class="p-1.5 bg-rose-50 text-rose-600 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-xl font-extrabold text-rose-600">{{ number_format($dashboardKpis['total_views'] ?? 0) }} <span class="text-xs font-semibold text-gray-400">views</span></p>
                        <p class="text-[11px] font-semibold text-gray-500 mt-0.5">{{ number_format($dashboardKpis['total_engagement'] ?? 0) }} total engagement</p>
                    </div>
                </div>
            </div>

            <!-- Missing Links Warning Alert Banner -->
            @php
                $missingLinksCount = $dashboardKpis['missing_links'] ?? ($missingLinksCount ?? 0);
            @endphp

            @if($missingLinksCount > 0)
                <div class="mb-5 p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="flex h-3 w-3 relative shrink-0">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                        </span>
                        <div>
                            <span class="text-sm font-bold block text-amber-900">
                                ⚠️ Missing Video Links Alert
                            </span>
                            <span class="text-xs text-amber-700">
                                There are <strong>{{ $missingLinksCount }}</strong> tasks created without Facebook or YouTube video links.
                            </span>
                        </div>
                    </div>
                    <a href="{{ route('tasks.index', array_merge(request()->except('page'), ['empty_link' => 'either'])) }}" 
                       class="text-xs bg-amber-600 hover:bg-amber-700 text-white font-bold px-3.5 py-2 rounded-lg transition shadow-sm inline-flex items-center gap-1.5 shrink-0">
                        ⚡ View Missing Links
                    </a>
                </div>
            @endif

            <!-- Scope Tabs & Export Action -->
            <div class="flex flex-wrap items-center justify-between border-b border-gray-200 mb-6 bg-white p-2 rounded-xl border border-gray-100 shadow-sm gap-2">
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <a href="{{ route('tasks.index', array_merge(request()->except('page'), ['scope' => 'all'])) }}" 
                       class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-semibold rounded-lg transition {{ (!request('scope') || request('scope') === 'all') ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50' }}">
                        All Tasks
                    </a>
                    <a href="{{ route('tasks.index', array_merge(request()->except('page'), ['scope' => 'my_tasks'])) }}" 
                       class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-semibold rounded-lg transition {{ request('scope') === 'my_tasks' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50' }}">
                        My Tasks
                    </a>
                    <a href="{{ route('tasks.index', array_merge(request()->except('page'), ['scope' => 'pending_edit'])) }}" 
                       class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-semibold rounded-lg transition {{ request('scope') === 'pending_edit' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50' }}">
                        My Pending Video Edits
                    </a>
                </div>

                <div class="flex items-center gap-2 sm:me-2">
                    @if(Auth::user()->hasPermission('export_tasks'))
                        <a href="{{ route('tasks.export', request()->all()) }}" 
                           class="inline-flex items-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm transition duration-150"
                           title="Export filtered tasks to CSV file for Excel">
                            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Export CSV Report
                        </a>
                    @endif
                </div>
            </div>

            <!-- Main Filtering Bar -->
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm mb-6">
                <form action="{{ route('tasks.index') }}" method="GET" id="filterForm">
                    <input type="hidden" name="scope" value="{{ request('scope', 'all') }}">
                    <div class="flex flex-wrap items-center gap-3">
                        
                        <!-- More Filter Option Toggle Button -->
                        <div class="relative w-full sm:w-auto">
                            <button type="button" @click="showMoreFilters = !showMoreFilters" 
                                class="w-full sm:w-auto justify-center inline-flex items-center px-4 py-2 border border-gray-200 rounded-lg text-sm font-semibold text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none transition">
                                <svg class="w-4 h-4 me-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                More Filter Option
                                <svg class="w-3.5 h-3.5 ms-2 text-gray-400 transition-transform duration-200" :class="showMoreFilters ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                        </div>

                        <!-- Team Dropdown -->
                        <div class="w-full sm:w-44">
                            <select name="team_id" onchange="document.getElementById('filterForm').submit()" 
                                class="w-full border-gray-200 rounded-lg text-sm bg-gray-50 focus:ring-indigo-100">
                                <option value="">Select Team</option>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}" {{ request('team_id') == $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- User (Entry By) Dropdown -->
                        <div class="w-full sm:w-44">
                            <select name="user_id" onchange="document.getElementById('filterForm').submit()" 
                                class="w-full border-gray-200 rounded-lg text-sm bg-gray-50 focus:ring-indigo-100">
                                <option value="">Select User</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Type Dropdown -->
                        <div class="w-full sm:w-40">
                            <select name="task_type_id" onchange="document.getElementById('filterForm').submit()" 
                                class="w-full border-gray-200 rounded-lg text-sm bg-gray-50 focus:ring-indigo-100">
                                <option value="">Select Type</option>
                                @foreach($taskTypes as $t)
                                    <option value="{{ $t->id }}" {{ request('task_type_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Date Range From -->
                        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full sm:w-auto">
                            <input type="date" name="from_date" value="{{ request('from_date') }}" 
                                class="flex-1 sm:flex-initial border-gray-200 rounded-lg text-sm bg-gray-50 focus:ring-indigo-100">
                            <span class="text-xs font-semibold text-gray-400">To</span>
                            <input type="date" name="to_date" value="{{ request('to_date') }}" 
                                class="flex-1 sm:flex-initial border-gray-200 rounded-lg text-sm bg-gray-50 focus:ring-indigo-100">
                            <button type="submit" class="bg-slate-800 text-white text-xs px-3 py-2.5 rounded-lg font-bold hover:bg-slate-700 transition">Go</button>
                        </div>

                        <!-- Search Box -->
                        <div class="flex-1 min-w-[200px]">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Title..." 
                                class="w-full border-gray-200 rounded-lg text-sm bg-gray-50 focus:ring-indigo-100">
                        </div>

                        <!-- Per Page -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-500 whitespace-nowrap">Per Page</span>
                            <select name="per_page" onchange="document.getElementById('filterForm').submit()" 
                                class="border-gray-200 rounded-lg text-sm bg-gray-50 focus:ring-indigo-100">
                                <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                <option value="50" {{ request('per_page', 50) == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </div>

                        @if(request()->anyFilled(['team_id', 'user_id', 'task_type_id', 'from_date', 'to_date', 'search', 'facebook_page_id', 'youtube_channel_id', 'area_id', 'tag', 'empty_link']))
                            <a href="{{ route('tasks.index') }}" class="text-xs text-red-500 hover:underline font-bold">Clear Filters</a>
                        @endif

                    </div>

                    <!-- Collapsible Extra Filters -->
                    <div x-show="showMoreFilters" x-transition 
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 mt-4 pt-4 border-t border-gray-100 bg-slate-50 p-4 rounded-lg">
                        
                        <!-- Facebook Page -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">Facebook Page</label>
                            <select name="facebook_page_id" onchange="document.getElementById('filterForm').submit()" 
                                class="w-full border-gray-200 rounded-lg text-xs bg-white focus:ring-indigo-100">
                                <option value="">Select Page</option>
                                @foreach($facebookPages as $page)
                                    <option value="{{ $page->id }}" {{ request('facebook_page_id') == $page->id ? 'selected' : '' }}>{{ $page->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- YouTube Channel -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">YouTube Channel</label>
                            <select name="youtube_channel_id" onchange="document.getElementById('filterForm').submit()" 
                                class="w-full border-gray-200 rounded-lg text-xs bg-white focus:ring-indigo-100">
                                <option value="">Select Channel</option>
                                @foreach($youtubeChannels as $chan)
                                    <option value="{{ $chan->id }}" {{ request('youtube_channel_id') == $chan->id ? 'selected' : '' }}>{{ $chan->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Area / News Source -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">Area / News Source</label>
                            <select name="area_id" onchange="document.getElementById('filterForm').submit()" 
                                class="w-full border-gray-200 rounded-lg text-xs bg-white focus:ring-indigo-100">
                                <option value="">Select Area</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tags filter -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">Tag Search</label>
                            <input type="text" name="tag" value="{{ request('tag') }}" placeholder="e.g. Nagad" 
                                class="w-full border-gray-200 rounded-lg text-xs bg-white focus:ring-indigo-100">
                        </div>

                        <!-- Link Empty Filter -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">Filter by empty Link</label>
                            <select name="empty_link" onchange="document.getElementById('filterForm').submit()" 
                                class="w-full border-gray-200 rounded-lg text-xs bg-white focus:ring-indigo-100">
                                <option value="">Show All Tasks</option>
                                <option value="facebook" {{ request('empty_link') == 'facebook' ? 'selected' : '' }}>Missing Facebook Link</option>
                                <option value="youtube" {{ request('empty_link') == 'youtube' ? 'selected' : '' }}>Missing YouTube Link</option>
                                <option value="both" {{ request('empty_link') == 'both' ? 'selected' : '' }}>Missing Both Links</option>
                                <option value="either" {{ request('empty_link') == 'either' ? 'selected' : '' }}>Missing Either Link (Pending)</option>
                            </select>
                        </div>

                    </div>
                </form>
            </div>

            <!-- 3. Bulk Task Actions Toolbar -->
            @if($canBulkActions)
                <div x-show="selectedTaskIds.length > 0" 
                     x-transition
                     style="display: none;"
                     x-cloak
                     class="sticky top-3 z-30 mb-4 p-3.5 bg-slate-900 text-white rounded-xl shadow-xl border border-slate-700 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-indigo-600 text-white font-extrabold text-xs" x-text="selectedTaskIds.length"></span>
                        <span class="text-xs sm:text-sm font-semibold">task(s) selected for bulk action</span>
                    </div>

                    <form action="{{ route('tasks.bulk-action') }}" method="POST" class="flex flex-wrap items-center gap-2" onsubmit="return confirm('Apply this bulk action to the selected tasks?');">
                        @csrf
                        <template x-for="id in selectedTaskIds" :key="id">
                            <input type="hidden" name="task_ids[]" :value="id">
                        </template>

                        <select name="bulk_type" x-model="bulkType" class="bg-slate-800 border-slate-700 text-white rounded-lg text-xs py-2 px-3 focus:ring-indigo-500">
                            <option value="assign_team">Assign Team</option>
                            <option value="assign_shift">Assign Shift</option>
                            <option value="assign_task_type">Assign Task Type</option>
                            @if(Auth::user()->hasPermission('delete_tasks'))
                                <option value="delete">Delete Selected</option>
                            @endif
                        </select>

                        <!-- Target ID for Assign Team -->
                        <template x-if="bulkType === 'assign_team'">
                            <select name="target_id" required class="bg-slate-800 border-slate-700 text-white rounded-lg text-xs py-2 px-3 focus:ring-indigo-500">
                                <option value="">Choose Team...</option>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                                @endforeach
                            </select>
                        </template>

                        <!-- Target ID for Assign Shift -->
                        <template x-if="bulkType === 'assign_shift'">
                            <select name="target_id" required class="bg-slate-800 border-slate-700 text-white rounded-lg text-xs py-2 px-3 focus:ring-indigo-500">
                                <option value="">Choose Shift...</option>
                                @foreach($shifts ?? [] as $shift)
                                    <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                                @endforeach
                            </select>
                        </template>

                        <!-- Target ID for Assign Task Type -->
                        <template x-if="bulkType === 'assign_task_type'">
                            <select name="target_id" required class="bg-slate-800 border-slate-700 text-white rounded-lg text-xs py-2 px-3 focus:ring-indigo-500">
                                <option value="">Choose Task Type...</option>
                                @foreach($taskTypes as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </template>

                        <button type="submit" 
                                :class="bulkType === 'delete' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-indigo-600 hover:bg-indigo-500'"
                                class="text-white text-xs font-bold px-4 py-2 rounded-lg shadow transition">
                            Apply Bulk Action
                        </button>
                        <button type="button" @click="selectedTaskIds = []" class="text-slate-300 hover:text-white text-xs font-semibold px-2.5 py-2">
                            Clear Selection
                        </button>
                    </form>
                </div>
            @endif

            <!-- Task Table Card -->
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                @if($canBulkActions)
                                    <th class="py-3 px-3 border-b border-gray-100 text-center w-10">
                                        <input type="checkbox" 
                                               @change="toggleSelectAll($event)"
                                               :checked="allPageTaskIds.length > 0 && selectedTaskIds.length === allPageTaskIds.length"
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                               title="Select All on Page">
                                    </th>
                                @endif
                                <th class="py-3 px-4 border-b border-gray-100 text-center w-12">ID</th>
                                <th class="py-3 px-4 border-b border-gray-100 whitespace-nowrap">Onair Date</th>
                                <th class="py-3 px-4 border-b border-gray-100 whitespace-nowrap">Onair Time</th>
                                <th class="py-3 px-4 border-b border-gray-100 text-center">Type</th>
                                <th class="py-3 px-4 border-b border-gray-100 whitespace-nowrap">Entry Date & Time</th>
                                <th class="py-3 px-6 border-b border-gray-100 min-w-[320px]">Title</th>
                                <th class="py-3 px-4 border-b border-gray-100 whitespace-nowrap">Entry By</th>
                                <th class="py-3 px-4 border-b border-gray-100 text-center">Facebook Link</th>
                                <th class="py-3 px-4 border-b border-gray-100 text-center">Youtube Link</th>
                                <th class="py-3 px-4 border-b border-gray-100">News Source</th>
                                <th class="py-3 px-4 border-b border-gray-100">Tag</th>
                                @foreach($activeColumns as $col)
                                    <th class="py-3 px-4 border-b border-gray-100 whitespace-nowrap text-center">{{ $col->name }}</th>
                                @endforeach
                                <th class="py-3 px-4 border-b border-gray-100 text-center w-24">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-600 bg-white">
                            @forelse($tasks as $task)
                                @php
                                    $canQuickEditThisTask = $canQuickUpdateLinks || Auth::user()->can('update', $task);
                                @endphp
                                <tr class="hover:bg-slate-50 transition duration-75" :class="selectedTaskIds.includes('{{ $task->id }}') ? 'bg-indigo-50/40' : ''">
                                    @if($canBulkActions)
                                        <td class="py-4 px-3 text-center">
                                            <input type="checkbox" value="{{ $task->id }}" x-model="selectedTaskIds" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                        </td>
                                    @endif
                                    <!-- ID -->
                                    <td class="py-4 px-4 text-center font-semibold text-gray-400 text-xs">{{ $task->id }}</td>
                                    
                                    <!-- On Air Date -->
                                    <td class="py-4 px-4 whitespace-nowrap font-semibold text-xs text-gray-500">
                                        {{ $task->on_air_date?->format('d M, Y') ?? 'N/A' }}
                                    </td>
                                    
                                    <!-- On Air Time -->
                                    <td class="py-4 px-4 whitespace-nowrap text-xs text-gray-500">
                                        {{ $task->on_air_time ?? '-' }}
                                    </td>
                                    
                                    <!-- Task Type Badge -->
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold shadow-sm" style="background-color: {{ $task->taskType?->color ?? '#e2e8f0' }}; color: #1e293b;">
                                            {{ $task->taskType?->name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    
                                    <!-- Entry Date & Time -->
                                    <td class="py-4 px-4 whitespace-nowrap text-xs text-gray-400">
                                        {{ $task->created_at?->format('d/m/Y h:i A') ?? '-' }}
                                    </td>
                                    
                                    <!-- Title (Bengali/English) -->
                                    <td class="py-4 px-6 font-semibold text-gray-900 text-sm leading-relaxed animate-fade-in">
                                        <div class="flex items-center gap-2">
                                            <span>{{ $task->title }}</span>
                                            <div id="comment-badge-container-{{ $task->id }}" class="inline-flex">
                                                @if($task->comments_count > 0)
                                                    <a href="{{ route('tasks.edit', $task->id) }}#comments-loop" class="inline-flex items-center gap-0.5 bg-indigo-50 border border-indigo-200 text-indigo-700 px-1.5 py-0.5 rounded text-[10px] font-bold hover:bg-indigo-100 transition shadow-sm" title="View feedback and comments">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                                        <span id="comment-badge-count-{{ $task->id }}">{{ $task->comments_count }}</span>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- Entry By -->
                                    <td class="py-4 px-4 whitespace-nowrap text-xs font-bold text-indigo-600">
                                        {{ $task->entryUser?->name ?? 'System' }}
                                    </td>
                                    
                                    <!-- FB Link Column -->
                                    <td class="py-4 px-4 text-center">
                                        @if($task->facebook_url)
                                            <div class="flex flex-col items-center gap-1">
                                                <div class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-2 py-1 rounded text-xs font-semibold border border-blue-100 hover:bg-blue-100 transition">
                                                    <a href="{{ $task->facebook_url }}" target="_blank" class="truncate max-w-[80px]">
                                                        {{ $task->facebookPage?->name ?? 'Facebook' }}
                                                    </a>
                                                    <button type="button" onclick="navigator.clipboard.writeText(@js($task->facebook_url)); alert('Link copied!')" class="text-blue-400 hover:text-blue-700">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                                    </button>
                                                </div>
                                                <div class="flex items-center justify-center gap-1.5 mt-0.5">
                                                    @if($task->views > 0 || $task->reach > 0)
                                                        <span class="text-[10px] text-gray-500 font-semibold" title="Views | Reach">
                                                            👁️ {{ number_format($task->views) }} | 📢 {{ number_format($task->reach) }}
                                                        </span>
                                                    @endif
                                                    @if(Auth::user()->tenant?->facebook_page_access_token && Auth::user()->hasPermission('sync_social_metrics'))
                                                        <form action="{{ route('tasks.sync-stats', $task->id) }}" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="text-gray-400 hover:text-blue-600 transition" title="Sync Facebook Stats">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18v3z"></path></svg>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if($task->api_sync_error && str_contains($task->api_sync_error, 'Facebook'))
                                                        <span class="text-red-500 cursor-pointer text-xs" title="{{ $task->api_sync_error }}">⚠️</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <div class="inline-flex items-center gap-1">
                                                <span class="text-xs text-red-500 bg-red-50 border border-red-100 px-2 py-0.5 rounded font-bold">Pending</span>
                                                @if($canQuickEditThisTask)
                                                    <button type="button" @click="openQuickLinkModal({{ $task->id }})" class="text-[10px] bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-bold px-1.5 py-0.5 rounded transition" title="Quick-add Facebook/YouTube link">
                                                        + Add Link
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    
                                    <!-- YT Link Column -->
                                    <td class="py-4 px-4 text-center">
                                        @if($task->youtube_url)
                                            <div class="flex flex-col items-center gap-1">
                                                <div class="inline-flex items-center gap-1 bg-rose-50 text-rose-700 px-2 py-1 rounded text-xs font-semibold border border-rose-100 hover:bg-rose-100 transition">
                                                    <a href="{{ $task->youtube_url }}" target="_blank" class="truncate max-w-[80px]">
                                                        {{ $task->youtubeChannel?->name ?? 'Youtube' }}
                                                    </a>
                                                    <button type="button" onclick="navigator.clipboard.writeText(@js($task->youtube_url)); alert('Link copied!')" class="text-rose-400 hover:text-rose-700">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                                    </button>
                                                </div>
                                                <div class="flex items-center justify-center gap-1.5 mt-0.5">
                                                    @if($task->views > 0 || $task->likes > 0)
                                                        <span class="text-[10px] text-gray-500 font-semibold" title="Views | Likes">
                                                            👁️ {{ number_format($task->views) }} | 👍 {{ number_format($task->likes) }}
                                                        </span>
                                                    @endif
                                                    @if(Auth::user()->tenant?->youtube_api_key && Auth::user()->hasPermission('sync_social_metrics'))
                                                        <form action="{{ route('tasks.sync-stats', $task->id) }}" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="text-gray-400 hover:text-rose-600 transition" title="Sync YouTube Stats">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18v3z"></path></svg>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if($task->api_sync_error && str_contains($task->api_sync_error, 'YouTube'))
                                                        <span class="text-red-500 cursor-pointer text-xs" title="{{ $task->api_sync_error }}">⚠️</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <div class="inline-flex items-center gap-1">
                                                <span class="text-xs text-red-500 bg-red-50 border border-red-100 px-2 py-0.5 rounded font-bold">Pending</span>
                                                @if($canQuickEditThisTask)
                                                    <button type="button" @click="openQuickLinkModal({{ $task->id }})" class="text-[10px] bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold px-1.5 py-0.5 rounded transition" title="Quick-add Facebook/YouTube link">
                                                        + Add Link
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    
                                    <!-- News Source (Area) -->
                                    <td class="py-4 px-4">
                                        @if($task->area)
                                            <span class="inline-flex items-center bg-gray-50 border border-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs">
                                                {{ $task->area->name }}
                                            </span>
                                        @else
                                            <span class="text-gray-300 text-xs">-</span>
                                        @endif
                                    </td>
                                    
                                    <!-- Tag -->
                                    <td class="py-4 px-4 font-semibold text-xs text-emerald-600">
                                        {{ $task->tags ? $task->tags : '-' }}
                                    </td>
                                    
                                    @foreach($activeColumns as $col)
                                        @php
                                            $assignedUser = $task->assignments->first(fn($a) => $a->designation_id == $col->id)?->user;
                                        @endphp
                                        <td class="py-4 px-4 text-center">
                                            @if($assignedUser)
                                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded" style="background-color: {{ $col->color ?? '#e2e8f0' }}; color: #1e293b; border: 1px solid #cbd5e1;">
                                                    {{ $assignedUser->name }}
                                                </span>
                                            @else
                                                <span class="text-gray-300 text-xs">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    
                                    <!-- Actions (Edit/Delete) -->
                                    <td class="py-4 px-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <!-- View Details Button -->
                                            <button type="button" 
                                                    @click="openTaskModal({{ $task->id }})" 
                                                    class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 p-1.5 rounded transition"
                                                    title="View Full Task Details">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </button>
                                            @can('update', $task)
                                                <a href="{{ route('tasks.edit', $task->id) }}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 p-1.5 rounded transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                </a>
                                            @endcan
                                            @can('delete', $task)
                                                <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this task?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-rose-600 hover:text-rose-900 bg-rose-50 hover:bg-rose-100 p-1.5 rounded transition">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </form>
                                            @endcan
                                            @if(Auth::user()->cannot('update', $task) && Auth::user()->cannot('delete', $task))
                                                <span class="text-xs text-gray-400 font-semibold italic">No Access</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="16" class="py-8 text-center text-gray-400">
                                        No tasks found matching current filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination footer -->
                @if($tasks->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $tasks->links() }}
                    </div>
                @endif
            </div>

            <!-- 4. Inline Quick-Link Updater Modal -->
            <div x-show="showQuickLinkModal" 
                 class="fixed inset-0 z-[9999] overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 style="display: none;"
                 x-cloak
                 @keydown.escape.window="showQuickLinkModal = false">
                <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-lg w-full overflow-hidden" @click.away="showQuickLinkModal = false">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-slate-50">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 block">Quick Link Updater</span>
                            <h3 class="font-bold text-gray-900 text-sm sm:text-base truncate max-w-sm" x-text="'#' + (quickLinkTask?.id || '') + ' — ' + (quickLinkTask?.title || '')"></h3>
                        </div>
                        <button type="button" @click="showQuickLinkModal = false" class="text-gray-400 hover:text-gray-600 font-bold text-2xl leading-none">&times;</button>
                    </div>

                    <form method="POST" :action="'/tasks/' + (quickLinkTask?.id || '') + '/quick-links'" class="p-6 space-y-4">
                        @csrf
                        @method('PATCH')

                        <!-- Facebook Page & URL -->
                        <div class="p-3.5 rounded-xl bg-blue-50/50 border border-blue-100 space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-blue-800 mb-1">Facebook Page</label>
                                <select name="facebook_page_id" x-model="quickLinkTask.facebookPageId" class="w-full border-gray-200 rounded-lg text-xs bg-white focus:ring-indigo-100">
                                    <option value="">Select Facebook Page (Optional)</option>
                                    @foreach($facebookPages as $page)
                                        <option value="{{ $page->id }}">{{ $page->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-blue-800 mb-1">Facebook Video URL</label>
                                <input type="url" name="facebook_url" x-model="quickLinkTask.facebookUrl" placeholder="https://www.facebook.com/..." class="w-full border-gray-200 rounded-lg text-xs bg-white focus:ring-indigo-100">
                            </div>
                        </div>

                        <!-- YouTube Channel & URL -->
                        <div class="p-3.5 rounded-xl bg-rose-50/50 border border-rose-100 space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-rose-800 mb-1">YouTube Channel</label>
                                <select name="youtube_channel_id" x-model="quickLinkTask.youtubeChannelId" class="w-full border-gray-200 rounded-lg text-xs bg-white focus:ring-indigo-100">
                                    <option value="">Select YouTube Channel (Optional)</option>
                                    @foreach($youtubeChannels as $chan)
                                        <option value="{{ $chan->id }}">{{ $chan->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-rose-800 mb-1">YouTube Video URL</label>
                                <input type="url" name="youtube_url" x-model="quickLinkTask.youtubeUrl" placeholder="https://www.youtube.com/watch?v=..." class="w-full border-gray-200 rounded-lg text-xs bg-white focus:ring-indigo-100">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="showQuickLinkModal = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2 px-4 rounded-lg transition">
                                Cancel
                            </button>
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 px-5 rounded-lg shadow transition">
                                Save Publishing Links
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 2. Live Activity Log Slide-Over Drawer -->
            @if(Auth::user()->hasPermission('view_activity_logs'))
                <div x-show="showActivityDrawer" 
                     class="fixed inset-0 z-[9999] overflow-hidden"
                     style="display: none;"
                     x-cloak
                     @keydown.escape.window="showActivityDrawer = false">
                    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" 
                         x-show="showActivityDrawer"
                         x-transition.opacity
                         @click="showActivityDrawer = false"></div>

                    <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
                        <div class="w-screen max-w-md bg-white shadow-2xl border-l border-gray-100 flex flex-col"
                             x-show="showActivityDrawer"
                             x-transition:enter="transform transition ease-in-out duration-300"
                             x-transition:enter-start="translate-x-full"
                             x-transition:enter-end="translate-x-0"
                             x-transition:leave="transform transition ease-in-out duration-200"
                             x-transition:leave-start="translate-x-0"
                             x-transition:leave-end="translate-x-full">
                            
                            <div class="p-5 bg-slate-900 text-white flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">🕒</span>
                                    <div>
                                        <h3 class="font-bold text-sm sm:text-base">Recent Activity Audit Stream</h3>
                                        <p class="text-[11px] text-slate-400">Real-time organization actions & changes</p>
                                    </div>
                                </div>
                                <button type="button" @click="showActivityDrawer = false" class="text-slate-400 hover:text-white text-2xl font-bold leading-none">&times;</button>
                            </div>

                            <div class="p-4 flex-1 overflow-y-auto space-y-3 bg-slate-50/60">
                                @forelse($recentLogs ?? [] as $log)
                                    @php
                                        $badgeClass = match($log->action) {
                                            'created', 'staff_created', 'designation_created' => 'bg-emerald-100 text-emerald-800',
                                            'updated', 'link_updated', 'bulk_updated', 'permissions_updated', 'column_toggled', 'settings_updated', 'form_config_updated', 'webhooks_updated', 'api_keys_updated' => 'bg-amber-100 text-amber-800',
                                            'deleted', 'bulk_deleted', 'designation_deleted' => 'bg-rose-100 text-rose-800',
                                            'exported_csv' => 'bg-indigo-100 text-indigo-800',
                                            'attendance_checkin', 'attendance_checkout', 'leave_requested' => 'bg-purple-100 text-purple-800',
                                            default => 'bg-slate-200 text-slate-800',
                                        };
                                    @endphp
                                    <div class="bg-white p-3.5 rounded-xl border border-gray-100 shadow-sm flex flex-col gap-1.5 text-xs">
                                        <div class="flex justify-between items-start gap-2">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span class="font-bold text-gray-900">{{ $log->user?->name ?? 'System' }}</span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $badgeClass }}">
                                                    {{ str_replace('_', ' ', $log->action) }}
                                                </span>
                                            </div>
                                            <span class="text-[10px] text-gray-400 font-semibold shrink-0">{{ $log->created_at?->diffForHumans() }}</span>
                                        </div>

                                        @if($log->task_title || $log->task_id)
                                            <p class="text-gray-600 font-medium">
                                                @if($log->task_id) <span class="text-gray-400">#{{ $log->task_id }}</span> @endif
                                                {{ $log->task_title }}
                                            </p>
                                        @endif

                                        @if(is_array($log->changes) && count($log->changes) > 0)
                                            <div class="mt-1 bg-slate-50 p-2 rounded-lg border border-gray-100 space-y-1 text-[11px]">
                                                @foreach($log->changes as $field => $diff)
                                                    @php
                                                        $oldVal = is_array($diff) && array_key_exists('old', $diff) ? $diff['old'] : null;
                                                        $newVal = is_array($diff) && array_key_exists('new', $diff) ? $diff['new'] : $diff;
                                                        $oldStr = is_array($oldVal) ? implode(', ', $oldVal) : (is_scalar($oldVal) ? (string)$oldVal : '');
                                                        $newStr = is_array($newVal) ? implode(', ', $newVal) : (is_scalar($newVal) ? (string)$newVal : '');
                                                    @endphp
                                                    <div class="flex flex-wrap items-center gap-1 text-gray-600">
                                                        <span class="font-semibold text-gray-500">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span>
                                                        @if($oldStr !== '')
                                                            <span class="line-through text-rose-500 bg-rose-50 px-1 rounded">{{ $oldStr }}</span>
                                                            <span class="text-gray-400">&rarr;</span>
                                                        @endif
                                                        <span class="text-emerald-700 font-bold bg-emerald-50 px-1 rounded">{{ $newStr !== '' ? $newStr : 'empty' }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="text-center py-12 text-xs text-gray-400 italic">
                                        No recent activity logs recorded yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Beautiful Alpine.js Task Details Modal -->
            <div x-show="showModal" 
                 class="fixed inset-0 z-[9999] overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 style="display: none;"
                 x-cloak
                 @keydown.escape.window="showModal = false">
                
                <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-3xl w-full max-h-[90vh] overflow-y-auto transform transition-all scale-100 flex flex-col"
                     @click.away="showModal = false">
                    
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-2xl">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded text-xs font-bold text-slate-800"
                                  :style="'background-color: ' + (activeTask?.taskColor || '#e2e8f0')"
                                  x-text="activeTask?.taskType"></span>
                            <span class="text-xs text-gray-400 font-bold" x-text="'ID: #' + activeTask?.id"></span>
                        </div>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 font-bold text-2xl focus:outline-none">&times;</button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-6 flex-1">
                        <!-- API Sync Warning Banner -->
                        <template x-if="activeTask?.apiSyncError">
                            <div class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-xs flex items-start gap-2 shadow-sm">
                                <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <div>
                                    <strong class="font-bold">Sync API Issue:</strong>
                                    <span x-text="activeTask?.apiSyncError"></span>
                                </div>
                            </div>
                        </template>

                        <!-- Title -->
                        <div>
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Task Title</h4>
                            <p class="text-lg font-bold text-gray-900 leading-snug" x-text="activeTask?.title"></p>
                        </div>

                        <!-- 2 Column Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-100 pt-6">
                            <!-- Left Column -->
                            <div class="space-y-4">
                                <div>
                                    <span class="text-xs font-bold text-gray-400 block mb-1">Shift / Group</span>
                                    <span class="inline-flex bg-indigo-50 border border-indigo-100 text-indigo-700 font-semibold px-2 py-0.5 rounded text-xs" x-text="activeTask?.shift"></span>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-gray-400 block mb-1">On Air Date & Time</span>
                                    <span class="text-sm font-semibold text-gray-800" x-text="(activeTask?.date || '') + ' at ' + (activeTask?.time || '')"></span>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-gray-400 block mb-1">Area / Source</span>
                                    <span class="text-sm font-semibold text-gray-800" x-text="activeTask?.area"></span>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-gray-400 block mb-1">Team</span>
                                    <span class="text-sm font-semibold text-gray-800" x-text="activeTask?.team"></span>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-gray-400 block mb-1">Tags</span>
                                    <span class="text-xs font-semibold text-emerald-600" x-text="activeTask?.tags || '-'"></span>
                                </div>
                                <!-- Custom Fields -->
                                <template x-for="(meta, key) in customFieldsMeta" :key="key">
                                    <template x-if="activeTask?.customFields && activeTask.customFields[key]">
                                        <div>
                                            <span class="text-xs font-bold text-gray-400 block mb-1" x-text="meta.label"></span>
                                            <span class="text-sm font-semibold text-gray-800" x-text="activeTask.customFields[key]"></span>
                                        </div>
                                    </template>
                                </template>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-4">
                                <div>
                                    <span class="text-xs font-bold text-gray-400 block mb-1">Assigned Staff Members</span>
                                    <div class="space-y-1.5 text-xs">
                                        <template x-for="assign in activeTask?.assignments || []" :key="assign.role">
                                            <div class="flex justify-between border-b border-gray-50 pb-1">
                                                <span class="text-gray-500" x-text="assign.role + ':'"></span>
                                                <span class="font-bold text-gray-800" x-text="assign.name"></span>
                                            </div>
                                        </template>
                                        <template x-if="!(activeTask?.assignments?.length)">
                                            <p class="text-gray-400 italic text-[11px]">No staff assigned.</p>
                                        </template>
                                    </div>
                                </div>

                                <div>
                                    <span class="text-xs font-bold text-gray-400 block mb-1">Social Media Links</span>
                                    <div class="space-y-2 text-xs">
                                        <!-- Facebook -->
                                        <div>
                                            <span class="text-gray-500 block mb-0.5" x-text="'Facebook Page: ' + (activeTask?.facebookPage || 'Pending')"></span>
                                            <template x-if="activeTask?.facebookUrl">
                                                <a :href="activeTask.facebookUrl" target="_blank" class="text-blue-600 hover:underline font-bold break-all block" x-text="activeTask.facebookUrl"></a>
                                            </template>
                                            <template x-if="!activeTask?.facebookUrl">
                                                <span class="text-red-500 font-bold uppercase text-[10px]">Pending Upload ✖</span>
                                            </template>
                                        </div>
                                        <!-- YouTube -->
                                        <div class="pt-1">
                                            <span class="text-gray-500 block mb-0.5" x-text="'YouTube Channel: ' + (activeTask?.youtubeChannel || 'Pending')"></span>
                                            <template x-if="activeTask?.youtubeUrl">
                                                <a :href="activeTask.youtubeUrl" target="_blank" class="text-rose-600 hover:underline font-bold break-all block" x-text="activeTask.youtubeUrl"></a>
                                            </template>
                                            <template x-if="!activeTask?.youtubeUrl">
                                                <span class="text-red-500 font-bold uppercase text-[10px]">Pending Upload ✖</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Performance Metrics (Views, Likes, Reach) -->
                                <div class="border-t border-gray-100 pt-3 mt-3">
                                    <span class="text-xs font-bold text-gray-400 block mb-1.5">Performance Statistics</span>
                                    <div class="grid grid-cols-3 gap-2 text-center">
                                        <div class="bg-gray-50 p-2 rounded-lg border border-gray-100">
                                            <span class="text-[10px] text-gray-400 block font-bold uppercase">Views</span>
                                            <span class="text-sm font-bold text-gray-800" x-text="activeTask?.views || 0"></span>
                                        </div>
                                        <div class="bg-gray-50 p-2 rounded-lg border border-gray-100">
                                            <span class="text-[10px] text-gray-400 block font-bold uppercase">Likes</span>
                                            <span class="text-sm font-bold text-gray-800" x-text="activeTask?.likes || 0"></span>
                                        </div>
                                        <div class="bg-gray-50 p-2 rounded-lg border border-gray-100">
                                            <span class="text-[10px] text-gray-400 block font-bold uppercase">Reach</span>
                                            <span class="text-sm font-bold text-gray-800" x-text="activeTask?.reach || 0"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks Section -->
                        <div class="border-t border-gray-100 pt-6">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Remarks</h4>
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 text-sm text-gray-700 whitespace-pre-line min-h-[60px]" x-text="activeTask?.remarks || 'No remarks provided.'"></div>
                        </div>

                        <!-- Media Attachments Section (Thumbnails & Scripts) -->
                        <div class="border-t border-gray-100 pt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Thumbnail -->
                            <div>
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Thumbnail Attachment</h4>
                                <template x-if="activeTask?.thumbnail">
                                    <div class="border border-gray-100 p-2 rounded-xl bg-slate-50 flex flex-col gap-2">
                                        <img :src="activeTask.thumbnail" class="w-full h-36 object-cover rounded-lg shadow-sm" alt="Thumbnail Preview">
                                        <a :href="activeTask.thumbnail" target="_blank" class="text-xs text-center font-bold text-indigo-600 hover:underline">Open Full Image &rarr;</a>
                                    </div>
                                </template>
                                <template x-if="!activeTask?.thumbnail">
                                    <p class="text-xs text-gray-400 italic">No thumbnail uploaded.</p>
                                </template>
                            </div>

                            <!-- Script -->
                            <div>
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Script Attachment</h4>
                                <template x-if="activeTask?.script">
                                    <div class="border border-gray-100 p-4 rounded-xl bg-slate-50 flex items-center gap-3">
                                        <svg class="w-10 h-10 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        <div>
                                            <p class="text-xs font-bold text-gray-800">Script Document is attached</p>
                                            <a :href="activeTask.script" download class="text-[11px] font-bold text-indigo-600 hover:underline">Download Script File &darr;</a>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!activeTask?.script">
                                    <p class="text-xs text-gray-400 italic">No script file uploaded.</p>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-gray-100 flex justify-between items-center bg-gray-50 rounded-b-2xl">
                        <span class="text-[10px] text-gray-400 font-bold uppercase" x-text="'Logged By: ' + activeTask?.entryUser"></span>
                        <div class="flex gap-2">
                            <template x-if="activeTask?.canEdit">
                                <a :href="activeTask?.editUrl" 
                                   class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 px-4 rounded-lg shadow transition">
                                    Edit Task
                                </a>
                            </template>
                            <button @click="showModal = false" 
                                    class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold py-2 px-4 rounded-lg shadow-sm transition">
                                Close
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>

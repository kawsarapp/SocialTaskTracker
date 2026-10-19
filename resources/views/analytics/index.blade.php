<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Analytics Dashboard') }}
        </h2>
    </x-slot>

    <!-- Load Chart.js from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
            
            <!-- Summary Metric Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
                <!-- Total Tasks -->
                <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl p-4 sm:p-6 text-white shadow-lg border border-indigo-100/10">
                    <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-indigo-100">Total Tasks Logged</p>
                    <p class="text-2xl sm:text-3xl font-extrabold mt-2">{{ number_format($summary['total_tasks']) }}</p>
                </div>
                <!-- Active Staff -->
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-4 sm:p-6 text-white shadow-lg border border-purple-100/10">
                    <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-purple-100">Active Staff Accounts</p>
                    <p class="text-2xl sm:text-3xl font-extrabold mt-2">{{ number_format($summary['total_staff']) }}</p>
                </div>
                <!-- Custom Shifts -->
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-4 sm:p-6 text-white shadow-lg border border-emerald-100/10">
                    <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-emerald-100">Configured Shifts</p>
                    <p class="text-2xl sm:text-3xl font-extrabold mt-2">{{ number_format($summary['total_shifts']) }}</p>
                </div>
                <!-- News Sources / Areas -->
                <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl p-4 sm:p-6 text-white shadow-lg border border-amber-100/10">
                    <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-amber-100">Areas / Sources</p>
                    <p class="text-2xl sm:text-3xl font-extrabold mt-2">{{ number_format($summary['total_areas']) }}</p>
                </div>
            </div>

            <!-- Social Media Views & Engagement Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Views -->
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Video Views</p>
                        <p class="text-3xl font-extrabold mt-2 text-rose-600">{{ number_format($summary['total_views']) }}</p>
                    </div>
                    <div class="p-3 bg-rose-50 text-rose-500 rounded-2xl">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </div>
                </div>
                <!-- Total Likes -->
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Likes</p>
                        <p class="text-3xl font-extrabold mt-2 text-blue-600">{{ number_format($summary['total_likes']) }}</p>
                    </div>
                    <div class="p-3 bg-blue-50 text-blue-500 rounded-2xl">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.757a2.243 2.243 0 002.243-2.243V5.243A2.243 2.243 0 0019 3h-5.243a2.243 2.243 0 00-2.243 2.243V10m0 0H7.243A2.243 2.243 0 005 12.243v4.514A2.243 2.243 0 007.243 19h5.243a2.243 2.243 0 002.243-2.243V10z"></path></svg>
                    </div>
                </div>
                <!-- Total Reach -->
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Facebook Est. Reach</p>
                        <p class="text-3xl font-extrabold mt-2 text-emerald-600">{{ number_format($summary['total_reach']) }}</p>
                    </div>
                    <div class="p-3 bg-emerald-50 text-emerald-500 rounded-2xl">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Main Charts Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- Content Type Breakdown -->
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm mb-1">Content Type Breakdown</h3>
                        <p class="text-xs text-gray-400 mb-6">Distribution of task types produced.</p>
                    </div>
                    <div class="relative w-full aspect-square flex items-center justify-center">
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>

                <!-- Platform Link Distribution -->
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm mb-1">Publishing Platform Share</h3>
                        <p class="text-xs text-gray-400 mb-6">Split of tasks uploaded to Facebook, YouTube, or both.</p>
                    </div>
                    <div class="relative w-full aspect-square flex items-center justify-center">
                        <canvas id="platformChart"></canvas>
                    </div>
                </div>

                <!-- Shift Output Distribution -->
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm mb-1">Work Shift Outputs</h3>
                        <p class="text-xs text-gray-400 mb-6">Total content production logs per shift.</p>
                    </div>
                    <div class="relative w-full aspect-square flex items-center justify-center">
                        <canvas id="shiftChart"></canvas>
                    </div>
                </div>

            </div>

            <!-- Staff Productivity Performance Grid -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <h3 class="font-bold text-gray-800 text-base mb-1">Staff Productivity Leaderboard</h3>
                <p class="text-xs text-gray-400 mb-6">Total scripts written, voiceovers recorded, videos edited, thumbnails designed, and uploads managed by your team.</p>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    <!-- Chart canvas -->
                    <div class="w-full min-h-[300px]">
                        <canvas id="staffChart"></canvas>
                    </div>

                    <!-- Statistics list table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600">
                            <thead>
                                <tr class="border-b border-gray-100 text-xs font-bold uppercase text-gray-400">
                                    <th class="pb-3">Staff Member</th>
                                    <th class="pb-3 text-center">Scripts</th>
                                    <th class="pb-3 text-center">Voices</th>
                                    <th class="pb-3 text-center">Edits</th>
                                    <th class="pb-3 text-center">Thumbs</th>
                                    <th class="pb-3 text-center">Uploads</th>
                                    <th class="pb-3 text-center">Total Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($staffStats as $staff)
                                    <tr>
                                        <td class="py-3 font-semibold text-gray-800">{{ $staff['name'] }}</td>
                                        <td class="py-3 text-center text-indigo-600 font-semibold">{{ $staff['scripts'] }}</td>
                                        <td class="py-3 text-center text-purple-600 font-semibold">{{ $staff['voices'] }}</td>
                                        <td class="py-3 text-center text-emerald-600 font-semibold">{{ $staff['edits'] }}</td>
                                        <td class="py-3 text-center text-pink-600 font-semibold">{{ $staff['thumbs'] }}</td>
                                        <td class="py-3 text-center text-amber-600 font-semibold">{{ $staff['uploads'] }}</td>
                                        <td class="py-3 text-center font-bold text-gray-900">{{ $staff['total'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-4 text-center text-xs text-gray-400">No staff performance data logged yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Social Performance & Top Videos Leaderboard -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Page/Channel Views Breakdown (span-2) -->
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm lg:col-span-2 flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm mb-1">Views per Page & Channel</h3>
                        <p class="text-xs text-gray-400 mb-6">Views distribution comparing active Facebook Pages and YouTube Channels.</p>
                    </div>
                    <div class="w-full min-h-[300px]">
                        <canvas id="viewsChart"></canvas>
                    </div>
                </div>

                <!-- Top Performing Videos (span-1) -->
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm flex flex-col">
                    <h3 class="font-bold text-gray-800 text-sm mb-1">Top Performing Content</h3>
                    <p class="text-xs text-gray-400 mb-4">Highest performing videos based on views.</p>
                    
                    <div class="space-y-4 flex-1 overflow-y-auto max-h-[350px]">
                        @forelse($topTasks as $index => $topTask)
                            <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-xl border border-gray-100/50 hover:bg-gray-100 transition shadow-sm">
                                <span class="w-6 h-6 rounded-full bg-slate-800 text-white font-extrabold text-[11px] flex items-center justify-center shrink-0">
                                    {{ $index + 1 }}
                                </span>
                                @if($topTask->thumbnail_path)
                                    <img src="{{ asset('storage/' . $topTask->thumbnail_path) }}" class="w-16 h-10 object-cover rounded shadow-sm shrink-0 bg-gray-200">
                                @else
                                    <div class="w-16 h-10 bg-slate-200 text-slate-400 font-bold text-[9px] flex items-center justify-center rounded shrink-0">No Img</div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-bold text-gray-800 truncate" title="{{ $topTask->title }}">{{ $topTask->title }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-[9px] font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded">
                                            👁️ {{ number_format($topTask->views) }}
                                        </span>
                                        @if($topTask->reach > 0)
                                            <span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">
                                                📢 {{ number_format($topTask->reach) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 italic text-center py-8">No content metrics synchronized yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Chart Configuration Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Color Palettes
            const chartColors = {
                indigo: '#4f46e5',
                purple: '#8b5cf6',
                emerald: '#10b981',
                rose: '#f43f5e',
                amber: '#f59e0b',
                gray: '#e2e8f0',
                slate: '#64748b'
            };

            // 1. Content Type Doughnut Chart
            const typeData = {!! json_encode($taskTypeStats) !!};
            new Chart(document.getElementById('typeChart'), {
                type: 'doughnut',
                data: {
                    labels: typeData.map(d => d.name),
                    datasets: [{
                        data: typeData.map(d => d.count),
                        backgroundColor: typeData.map(d => d.color),
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                    }
                }
            });

            // 2. Platform Share Pie Chart
            const platformData = {!! json_encode($platformStats) !!};
            new Chart(document.getElementById('platformChart'), {
                type: 'pie',
                data: {
                    labels: platformData.map(d => d.name),
                    datasets: [{
                        data: platformData.map(d => d.count),
                        backgroundColor: platformData.map(d => d.color),
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                    }
                }
            });

            // 3. Shift Output Bar Chart
            const shiftData = {!! json_encode($shiftStats) !!};
            new Chart(document.getElementById('shiftChart'), {
                type: 'bar',
                data: {
                    labels: shiftData.map(d => d.name),
                    datasets: [{
                        label: 'Tasks Count',
                        data: shiftData.map(d => d.count),
                        backgroundColor: chartColors.purple,
                        borderRadius: 6,
                        maxBarThickness: 30
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f8fafc' }, ticks: { stepSize: 1 } },
                        x: { grid: { display: false } }
                    }
                }
            });

            // 4. Staff Performance Stacked Bar Chart
            const staffData = {!! json_encode($staffStats) !!};
            new Chart(document.getElementById('staffChart'), {
                type: 'bar',
                data: {
                    labels: staffData.map(d => d.name),
                    datasets: [
                        {
                            label: 'Scripts Written',
                            data: staffData.map(d => d.scripts),
                            backgroundColor: chartColors.indigo,
                            borderRadius: 4
                        },
                        {
                            label: 'Voiceovers Recorded',
                            data: staffData.map(d => d.voices),
                            backgroundColor: chartColors.purple,
                            borderRadius: 4
                        },
                        {
                            label: 'Videos Edited',
                            data: staffData.map(d => d.edits),
                            backgroundColor: chartColors.emerald,
                            borderRadius: 4
                        },
                        {
                            label: 'Thumbnails Designed',
                            data: staffData.map(d => d.thumbs),
                            backgroundColor: '#ec4899', // Pink
                            borderRadius: 4
                        },
                        {
                            label: 'Uploads Managed',
                            data: staffData.map(d => d.uploads),
                            backgroundColor: chartColors.amber,
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } }
                    },
                    scales: {
                        x: { stacked: true, grid: { display: false } },
                        y: { stacked: true, beginAtZero: true, grid: { color: '#f8fafc' } }
                    }
                }
            });

            // 5. Facebook Pages & YouTube Channels Views Chart
            const fbViews = {!! json_encode($fbPageViews) !!};
            const ytViews = {!! json_encode($ytChannelViews) !!};
            
            const viewsLabels = [...fbViews.map(p => p.name), ...ytViews.map(c => c.name)];
            const viewsCounts = [...fbViews.map(p => p.views), ...ytViews.map(c => c.views)];
            const viewsColors = [
                ...fbViews.map(() => '#1877f2'), // Facebook blue
                ...ytViews.map(() => '#ff0000')  // YouTube red
            ];

            new Chart(document.getElementById('viewsChart'), {
                type: 'bar',
                data: {
                    labels: viewsLabels,
                    datasets: [{
                        label: 'Accumulated Video Views',
                        data: viewsCounts,
                        backgroundColor: viewsColors,
                        borderRadius: 8,
                        maxBarThickness: 45
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f8fafc' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        });
    </script>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Analytics Dashboard') }}
        </h2>
    </x-slot>

    <!-- Load Chart.js from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- Summary Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Total Tasks -->
                <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl p-6 text-white shadow-lg border border-indigo-100/10">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-100">Total Tasks Logged</p>
                    <p class="text-3xl font-extrabold mt-2">{{ $summary['total_tasks'] }}</p>
                </div>
                <!-- Active Staff -->
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white shadow-lg border border-purple-100/10">
                    <p class="text-xs font-bold uppercase tracking-wider text-purple-100">Active Staff Accounts</p>
                    <p class="text-3xl font-extrabold mt-2">{{ $summary['total_staff'] }}</p>
                </div>
                <!-- Custom Shifts -->
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white shadow-lg border border-emerald-100/10">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-100">Configured Shifts</p>
                    <p class="text-3xl font-extrabold mt-2">{{ $summary['total_shifts'] }}</p>
                </div>
                <!-- News Sources / Areas -->
                <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl p-6 text-white shadow-lg border border-amber-100/10">
                    <p class="text-xs font-bold uppercase tracking-wider text-amber-100">Areas / Sources</p>
                    <p class="text-3xl font-extrabold mt-2">{{ $summary['total_areas'] }}</p>
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
                <p class="text-xs text-gray-400 mb-6">Total scripts written, voiceovers recorded, and videos edited by your team.</p>
                
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
                                        <td class="py-3 text-center font-bold text-gray-900">{{ $staff['total'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-4 text-center text-xs text-gray-400">No staff performance data logged yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12 } }
                    },
                    scales: {
                        x: { stacked: true, grid: { display: false } },
                        y: { stacked: true, beginAtZero: true, grid: { color: '#f8fafc' } }
                    }
                }
            });
        });
    </script>
</x-app-layout>

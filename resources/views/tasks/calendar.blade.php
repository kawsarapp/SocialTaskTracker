<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Content Calendar') }}
            </h2>
            <a href="{{ route('tasks.index') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs px-4 py-2 rounded-lg shadow-md transition">
                Table View
            </a>
        </div>
    </x-slot>

    <!-- Load FullCalendar CDN -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>

    @php
        $formConfig = Auth::user()->tenant->task_form_config ?? [];
        $customFieldsMeta = collect($formConfig)->filter(fn($f) => $f['is_custom'] ?? false)->toArray();
    @endphp

    <div class="py-8" x-data="{ 
        showModal: false,
        task: {},
        customFieldsMeta: {{ json_encode($customFieldsMeta) }}
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                <!-- Calendar Container -->
                <div id="calendar" class="min-h-[650px]"></div>
            </div>
        </div>

        <!-- Task Details Modal (Alpine.js) -->
        <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition>
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showModal = false"></div>

            <!-- Modal Panel -->
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-xl overflow-hidden shadow-2xl max-w-lg w-full z-10 border border-slate-100" @click.stop>
                    <!-- Header -->
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center" :style="'border-top: 4px solid ' + task.task_type_color">
                        <div>
                            <span class="px-2 py-0.5 rounded text-xs font-bold text-slate-800" :style="'background-color: ' + task.task_type_color" x-text="task.task_type"></span>
                            <h3 class="font-bold text-gray-900 text-lg mt-2" x-text="task.title_raw"></h3>
                        </div>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 font-bold text-2xl focus:outline-none">&times;</button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-4 text-sm text-gray-600">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-400 font-bold uppercase">On-Air Time</p>
                                <p class="font-semibold text-gray-800" x-text="task.on_air_time"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-bold uppercase">On-Air Date</p>
                                <p class="font-semibold text-gray-800" x-text="task.on_air_date"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-bold uppercase">Area / Source</p>
                                <p class="font-semibold text-gray-800" x-text="task.area || 'None'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-bold uppercase">Team Assignment</p>
                                <p class="font-semibold text-gray-800" x-text="task.team || 'None'"></p>
                            </div>
                            <!-- Custom Fields -->
                            <template x-for="(meta, key) in customFieldsMeta" :key="key">
                                <template x-if="task.custom_fields && task.custom_fields[key]">
                                    <div>
                                        <p class="text-xs text-gray-400 font-bold uppercase" x-text="meta.label"></p>
                                        <p class="font-semibold text-gray-800" x-text="task.custom_fields[key]"></p>
                                    </div>
                                </template>
                            </template>
                        </div>

                        <hr class="border-gray-100">

                        <!-- Staff Assignments -->
                        <div>
                            <p class="text-xs text-gray-400 font-bold uppercase mb-2">Assigned Staff</p>
                            <div class="grid grid-cols-3 gap-2">
                                <template x-for="assign in task.assignments || []" :key="assign.role">
                                    <div class="bg-slate-50 p-2 rounded border border-slate-100 text-center">
                                        <p class="text-[10px] text-gray-400 font-bold" x-text="assign.role"></p>
                                        <p class="font-semibold text-gray-700 text-xs truncate" x-text="assign.name"></p>
                                    </div>
                                </template>
                                <template x-if="!(task.assignments?.length)">
                                    <p class="text-xs text-gray-400 italic col-span-3">No staff assigned to this task.</p>
                                </template>
                            </div>
                        </div>

                        <hr class="border-gray-100">

                        <!-- Links -->
                        <div class="space-y-2">
                            <p class="text-xs text-gray-400 font-bold uppercase">Social Media Links</p>
                            <div>
                                <span class="font-bold text-xs text-blue-600 block mb-0.5">Facebook Page: <span class="text-gray-700 font-normal" x-text="task.facebook_page || 'Not Scoped'"></span></span>
                                <template x-if="task.facebook_url">
                                    <a :href="task.facebook_url" target="_blank" class="text-xs text-indigo-600 hover:underline break-all" x-text="task.facebook_url"></a>
                                </template>
                                <template x-if="!task.facebook_url">
                                    <span class="text-xs text-rose-500 font-bold">✖ Missing Facebook Link</span>
                                </template>
                            </div>
                            <div class="pt-2">
                                <span class="font-bold text-xs text-red-600 block mb-0.5">YouTube Channel: <span class="text-gray-700 font-normal" x-text="task.youtube_channel || 'Not Scoped'"></span></span>
                                <template x-if="task.youtube_url">
                                    <a :href="task.youtube_url" target="_blank" class="text-xs text-indigo-600 hover:underline break-all" x-text="task.youtube_url"></a>
                                </template>
                                <template x-if="!task.youtube_url">
                                    <span class="text-xs text-rose-500 font-bold">✖ Missing YouTube Link</span>
                                </template>
                            </div>
                        </div>

                        <template x-if="task.remarks">
                            <div>
                                <hr class="border-gray-100 my-3">
                                <p class="text-xs text-gray-400 font-bold uppercase">Remarks</p>
                                <p class="bg-gray-50 p-2.5 rounded border border-gray-100 text-xs text-gray-700 mt-1 whitespace-pre-line" x-text="task.remarks"></p>
                            </div>
                        </template>
                    </div>

                    <!-- Footer -->
                    <div class="p-6 bg-gray-50 border-t border-gray-100 flex justify-between items-center text-xs">
                        <span class="text-gray-400">Logged by: <span class="text-gray-600 font-semibold" x-text="task.entry_by"></span></span>
                        <div class="flex gap-2">
                            <button @click="showModal = false; task = {}" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-bold py-1.5 px-4 rounded-lg shadow-sm">Close</button>
                            <a :href="task.edit_url" class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-1.5 px-4 rounded-lg shadow-sm text-center">Edit Task</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Initialization Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var eventsData = {!! $eventsJson !!};

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: eventsData,
                eventClick: function(info) {
                    info.jsEvent.preventDefault(); // Prevent browser navigation

                    // Retrieve Alpine.js scope to populate modal state
                    var alpineScope = document.querySelector('[x-data]').__x.$data;
                    alpineScope.task = info.event.extendedProps;
                    alpineScope.task.title_raw = info.event.extendedProps.title_raw;
                    alpineScope.task.task_type_color = info.event.backgroundColor || '#4f46e5';
                    alpineScope.showModal = true;
                },
                eventTimeFormat: {
                    hour: 'numeric',
                    minute: '2-digit',
                    meridiem: 'short'
                },
                displayEventEnd: false,
                height: 'auto'
            });

            calendar.render();
        });
    </script>
</x-app-layout>

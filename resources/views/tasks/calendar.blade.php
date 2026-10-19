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
    <style>
        @media (max-width: 640px) {
            .fc .fc-toolbar {
                flex-direction: column;
                gap: 0.5rem;
            }
            .fc .fc-toolbar-title {
                font-size: 1.1rem;
            }
            .fc .fc-button {
                padding: 0.3rem 0.5rem;
                font-size: 0.75rem;
            }
        }
    </style>

    @php
        $formConfig = Auth::user()->tenant->task_form_config ?? [];
        $customFieldsMeta = collect($formConfig)->filter(fn($f) => $f['is_custom'] ?? false)->toArray();
    @endphp

    <div id="calendar-root" class="py-6 sm:py-8" 
         x-data="{ 
            showModal: false,
            task: {},
            customFieldsMeta: {{ json_encode($customFieldsMeta) }},
            openEventModal(event) {
                this.task = Object.assign({}, event.extendedProps, {
                    title_raw: event.extendedProps.title_raw,
                    task_type_color: event.backgroundColor || '#4f46e5'
                });
                this.showModal = true;
            }
         }"
         @open-calendar-modal.window="openEventModal($event.detail)"
         @keydown.escape.window="showModal = false">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 p-3 sm:p-6">
                <!-- Calendar Container -->
                <div id="calendar" class="min-h-[500px] sm:min-h-[650px]"></div>
            </div>
        </div>

        <!-- Task Details Modal (Alpine.js) -->
        <div x-show="showModal" 
             class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" 
             style="display: none;" 
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showModal = false"></div>

            <!-- Modal Panel -->
            <div class="bg-white rounded-2xl overflow-hidden shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col z-10 border border-slate-100 transform transition-all" @click.stop>
                <!-- Header -->
                <div class="p-4 sm:p-6 border-b border-gray-100 flex justify-between items-start gap-3" :style="'border-top: 4px solid ' + (task.task_type_color || '#4f46e5')">
                    <div>
                        <span class="px-2.5 py-0.5 rounded text-xs font-bold text-slate-800" :style="'background-color: ' + (task.task_type_color || '#e2e8f0')" x-text="task.task_type || 'N/A'"></span>
                        <h3 class="font-bold text-gray-900 text-base sm:text-lg mt-2 leading-snug" x-text="task.title_raw"></h3>
                    </div>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 font-bold text-2xl leading-none p-1 focus:outline-none">&times;</button>
                </div>

                <!-- Body -->
                <div class="p-4 sm:p-6 space-y-4 text-sm text-gray-600 overflow-y-auto flex-1">
                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <p class="text-xs text-gray-400 font-bold uppercase">On-Air Time</p>
                            <p class="font-semibold text-gray-800" x-text="task.on_air_time || '-'"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-bold uppercase">On-Air Date</p>
                            <p class="font-semibold text-gray-800" x-text="task.on_air_date || '-'"></p>
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
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <template x-for="assign in task.assignments || []" :key="assign.role">
                                <div class="bg-slate-50 p-2 rounded-lg border border-slate-100 text-center">
                                    <p class="text-[10px] text-gray-400 font-bold" x-text="assign.role"></p>
                                    <p class="font-semibold text-gray-700 text-xs truncate" x-text="assign.name"></p>
                                </div>
                            </template>
                            <template x-if="!(task.assignments?.length)">
                                <p class="text-xs text-gray-400 italic col-span-2 sm:col-span-3">No staff assigned to this task.</p>
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
                            <p class="bg-gray-50 p-2.5 rounded-lg border border-gray-100 text-xs text-gray-700 mt-1 whitespace-pre-line" x-text="task.remarks"></p>
                        </div>
                    </template>
                </div>

                <!-- Footer -->
                <div class="p-4 sm:p-6 bg-gray-50 border-t border-gray-100 flex flex-wrap justify-between items-center gap-2 text-xs">
                    <span class="text-gray-400">Logged by: <span class="text-gray-600 font-semibold" x-text="task.entry_by || 'System'"></span></span>
                    <div class="flex gap-2">
                        <button @click="showModal = false; task = {}" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-bold py-2 px-4 rounded-lg shadow-sm transition">Close</button>
                        <template x-if="task.can_edit !== false">
                            <a :href="task.edit_url" class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-2 px-4 rounded-lg shadow-sm text-center transition">Edit Task</a>
                        </template>
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

                    // Dispatch custom window event for Alpine.js v3
                    window.dispatchEvent(new CustomEvent('open-calendar-modal', {
                        detail: info.event
                    }));
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

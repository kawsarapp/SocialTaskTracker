<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Task') }}
        </h2>
    </x-slot>

    @php
        $formConfig = Auth::user()->tenant->task_form_config ?? [];
    @endphp

    <div class="py-6 sm:py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 flex flex-col md:flex-row">
                
                <!-- Left Info Panel -->
                <div class="bg-gray-50 p-5 sm:p-6 md:w-1/4 border-b md:border-b-0 md:border-e border-gray-100">
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Edit Task</h3>
                    <p class="text-sm text-gray-500">Modify the fields below to update task details.</p>
                </div>

                <!-- Form Panel -->
                <div class="p-5 sm:p-8 md:w-3/4">
                    <form action="{{ route('tasks.update', $task->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Title (Always visible and required) -->
                        <div class="mb-6">
                            <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">
                                Title <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <input type="text" name="title" id="title" required value="{{ old('title', $task->title) }}"
                                class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                            @error('title')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Configured Fields (Sorted by sort_order) -->
                        @foreach($formConfig as $fieldKey => $fieldVal)
                            @if(!($fieldVal['visible'] ?? true))
                                @continue
                            @endif

                            @php
                                $required = $fieldVal['required'] ?? false;
                                $requiredAttr = $required ? 'required' : '';
                            @endphp

                            @if($fieldVal['is_custom'] ?? false)
                                <!-- Custom Field Rendering -->
                                @php
                                    $currentVal = $task->custom_fields[$fieldKey] ?? '';
                                @endphp
                                <div class="mb-6">
                                    <label for="custom_{{ $fieldKey }}" class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ $fieldVal['label'] }}
                                        @if($required)<span class="text-rose-500 font-bold">*</span>@endif
                                    </label>
                                    @if(($fieldVal['type'] ?? 'text') === 'textarea')
                                        <textarea name="custom_fields[{{ $fieldKey }}]" id="custom_{{ $fieldKey }}" rows="3" {{ $requiredAttr }} placeholder="Enter {{ strtolower($fieldVal['label']) }}..."
                                            class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">{{ old("custom_fields.{$fieldKey}", $currentVal) }}</textarea>
                                    @elseif(($fieldVal['type'] ?? 'text') === 'number')
                                        <input type="number" name="custom_fields[{{ $fieldKey }}]" id="custom_{{ $fieldKey }}" value="{{ old("custom_fields.{$fieldKey}", $currentVal) }}" {{ $requiredAttr }} placeholder="Enter number..."
                                            class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                    @else
                                        <input type="text" name="custom_fields[{{ $fieldKey }}]" id="custom_{{ $fieldKey }}" value="{{ old("custom_fields.{$fieldKey}", $currentVal) }}" {{ $requiredAttr }} placeholder="Enter {{ strtolower($fieldVal['label']) }}..."
                                            class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                    @endif
                                    @error("custom_fields.{$fieldKey}")
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            @else
                                <!-- Standard Fields Rendering -->
                                @if($fieldKey === 'task_type_id')
                                    <!-- Task Type -->
                                    <div class="mb-6">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Task Type @if($required)<span class="text-rose-500 font-bold">*</span>@endif
                                        </label>
                                        <div class="flex flex-wrap gap-4 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                            @foreach($taskTypes as $type)
                                                <label class="inline-flex items-center text-sm text-gray-700 cursor-pointer">
                                                    <input type="radio" name="task_type_id" value="{{ $type->id }}" 
                                                        {{ $task->task_type_id == $type->id ? 'checked' : '' }} {{ $requiredAttr }} class="text-indigo-600 focus:ring-indigo-500">
                                                    <span class="ms-2 px-2 py-0.5 rounded text-xs font-semibold" style="background-color: {{ $type->color ?? '#e2e8f0' }}">
                                                        {{ $type->name }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                        @error('task_type_id')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @elseif($fieldKey === 'facebook_url')
                                    <!-- Facebook URL -->
                                    <div class="mb-6">
                                        <label for="facebook_url" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Facebook URL @if($required)<span class="text-rose-500 font-bold">*</span>@endif
                                        </label>
                                        <input type="url" name="facebook_url" id="facebook_url" value="{{ old('facebook_url', $task->facebook_url) }}" {{ $requiredAttr }} placeholder="https://facebook.com/..." 
                                            class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                        <div id="fb_thumbnail_preview" class="mt-2 {{ $task->facebook_url ? '' : 'hidden' }}">
                                            <p class="text-xs font-bold text-gray-500 mb-1">Facebook Video Preview:</p>
                                            <img src="" class="h-20 rounded border shadow-sm">
                                        </div>
                                        @error('facebook_url')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @elseif($fieldKey === 'facebook_page_id')
                                    <!-- Facebook Page Selector -->
                                    <div class="mb-6" x-data="{ selectedPage: '{{ $task->facebook_page_id }}' }">
                                        <div class="flex justify-between items-center mb-2">
                                            <label class="block text-sm font-semibold text-gray-700">
                                                Select Facebook Page @if($required)<span class="text-rose-500 font-bold">*</span>@endif
                                            </label>
                                            <button type="button" @click="selectedPage = ''; $refs.fbPageRadios.querySelectorAll('input[type=radio]').forEach(i => i.checked = false)" class="text-xs text-indigo-500 hover:text-indigo-700 font-semibold">clear</button>
                                        </div>
                                        <input type="hidden" name="facebook_page_id" value="">
                                        <div x-ref="fbPageRadios" class="flex flex-wrap gap-3 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                            @foreach($facebookPages as $page)
                                                <label class="inline-flex items-center text-xs text-gray-600 cursor-pointer">
                                                    <input type="radio" name="facebook_page_id" value="{{ $page->id }}" 
                                                        {{ $task->facebook_page_id == $page->id ? 'checked' : '' }} {{ $requiredAttr }} x-model="selectedPage" class="text-indigo-600 focus:ring-indigo-500">
                                                    <span class="ms-1.5">{{ $page->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        @error('facebook_page_id')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @elseif($fieldKey === 'youtube_url')
                                    <!-- YouTube URL -->
                                    <div class="mb-6">
                                        <label for="youtube_url" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Youtube URL @if($required)<span class="text-rose-500 font-bold">*</span>@endif
                                        </label>
                                        <input type="url" name="youtube_url" id="youtube_url" value="{{ old('youtube_url', $task->youtube_url) }}" {{ $requiredAttr }} placeholder="https://youtube.com/..." 
                                            class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                        <div id="yt_thumbnail_preview" class="mt-2 {{ $task->youtube_url ? '' : 'hidden' }}">
                                            <p class="text-xs font-bold text-gray-500 mb-1">YouTube Video Preview:</p>
                                            <img src="" class="h-20 rounded border shadow-sm">
                                        </div>
                                        @error('youtube_url')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @elseif($fieldKey === 'youtube_channel_id')
                                    <!-- YouTube Channel Selector -->
                                    <div class="mb-6" x-data="{ selectedChannel: '{{ $task->youtube_channel_id }}' }">
                                        <div class="flex justify-between items-center mb-2">
                                            <label class="block text-sm font-semibold text-gray-700">
                                                Select Youtube Channel @if($required)<span class="text-rose-500 font-bold">*</span>@endif
                                            </label>
                                            <button type="button" @click="selectedChannel = ''; $refs.ytChannelRadios.querySelectorAll('input[type=radio]').forEach(i => i.checked = false)" class="text-xs text-indigo-500 hover:text-indigo-700 font-semibold">clear</button>
                                        </div>
                                        <input type="hidden" name="youtube_channel_id" value="">
                                        <div x-ref="ytChannelRadios" class="flex flex-wrap gap-3 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                            @foreach($youtubeChannels as $chan)
                                                <label class="inline-flex items-center text-xs text-gray-600 cursor-pointer">
                                                    <input type="radio" name="youtube_channel_id" value="{{ $chan->id }}" 
                                                        {{ $task->youtube_channel_id == $chan->id ? 'checked' : '' }} {{ $requiredAttr }} x-model="selectedChannel" class="text-indigo-600 focus:ring-indigo-500">
                                                    <span class="ms-1.5">{{ $chan->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        @error('youtube_channel_id')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @elseif($fieldKey === 'on_air_time')
                                    <!-- On Air Time -->
                                    <div class="mb-6">
                                        <label for="on_air_time" class="block text-sm font-semibold text-gray-700 mb-2">
                                            On Air Time @if($required)<span class="text-rose-500 font-bold">*</span>@endif
                                        </label>
                                        <input type="text" name="on_air_time" id="on_air_time" {{ $requiredAttr }} value="{{ old('on_air_time', $task->on_air_time) }}"
                                            class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                        @error('on_air_time')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @elseif($fieldKey === 'on_air_date')
                                    <!-- On Air Date -->
                                    <div class="mb-6">
                                        <label for="on_air_date" class="block text-sm font-semibold text-gray-700 mb-2">
                                            On Air Date @if($required)<span class="text-rose-500 font-bold">*</span>@endif
                                        </label>
                                        <input type="date" name="on_air_date" id="on_air_date" {{ $requiredAttr }} value="{{ old('on_air_date', $task->on_air_date?->format('Y-m-d')) }}"
                                            class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                        @error('on_air_date')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @elseif($fieldKey === 'area_id')
                                    <!-- Area -->
                                    <div class="mb-6">
                                        <label for="area_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Area @if($required)<span class="text-rose-500 font-bold">*</span>@endif
                                        </label>
                                        <select name="area_id" id="area_id" {{ $requiredAttr }} 
                                            class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                            <option value="">Select Area</option>
                                            @foreach($areas as $area)
                                                <option value="{{ $area->id }}" {{ $task->area_id == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('area_id')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @elseif($fieldKey === 'team_id')
                                    <!-- Team -->
                                    <div class="mb-6">
                                        <label for="team_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Team Assignment @if($required)<span class="text-rose-500 font-bold">*</span>@endif
                                        </label>
                                        <select name="team_id" id="team_id" {{ $requiredAttr }} 
                                            class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                            <option value="">Select Team</option>
                                            @foreach($teams as $team)
                                                <option value="{{ $team->id }}" {{ $task->team_id == $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('team_id')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @elseif($fieldKey === 'shift_id')
                                    <!-- Shift -->
                                    <div class="mb-6">
                                        <label for="shift_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Shift @if($required)<span class="text-rose-500 font-bold">*</span>@endif
                                        </label>
                                        <select name="shift_id" id="shift_id" {{ $requiredAttr }} 
                                            class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                            <option value="">Select Shift</option>
                                            @foreach($shifts as $shift)
                                                <option value="{{ $shift->id }}" {{ $task->shift_id == $shift->id ? 'selected' : '' }}>{{ $shift->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('shift_id')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @elseif($fieldKey === 'tags')
                                    <!-- Tags -->
                                    <div class="mb-6">
                                        <label for="tags" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Tags @if($required)<span class="text-rose-500 font-bold">*</span>@endif
                                        </label>
                                        <input type="text" name="tags" id="tags" value="{{ old('tags', $task->tags) }}" {{ $requiredAttr }} placeholder="e.g. Nagad, election" 
                                            class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                        @error('tags')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @elseif($fieldKey === 'remarks')
                                    <!-- Remarks -->
                                    <div class="mb-6">
                                        <label for="remarks" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Remarks @if($required)<span class="text-rose-500 font-bold">*</span>@endif
                                        </label>
                                        <textarea name="remarks" id="remarks" rows="4" {{ $requiredAttr }} placeholder="Enter remarks..." 
                                            class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">{{ old('remarks', $task->remarks) }}</textarea>
                                        @error('remarks')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @elseif($fieldKey === 'thumbnail')
                                    <!-- Thumbnail -->
                                    <div class="mb-6">
                                        <label for="thumbnail" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Thumbnail @if($required)<span class="text-rose-500 font-bold">*</span>@endif
                                        </label>
                                        @if($task->thumbnail_path)
                                            <div class="mb-3 flex items-center gap-3 bg-slate-50 p-2.5 rounded-lg border border-gray-100">
                                                <img src="{{ asset('storage/' . $task->thumbnail_path) }}" class="w-16 h-10 object-cover rounded shadow" alt="Current Thumbnail">
                                                <div>
                                                    <p class="text-[10px] text-gray-400 font-bold uppercase">Current File</p>
                                                    <a href="{{ asset('storage/' . $task->thumbnail_path) }}" target="_blank" class="text-xs text-indigo-600 hover:underline truncate max-w-[150px] block">View Image</a>
                                                </div>
                                            </div>
                                        @endif
                                        <input type="file" name="thumbnail" id="thumbnail" {{ $requiredAttr }} accept="image/*" 
                                            class="w-full border border-gray-200 rounded-lg text-xs p-2 focus:border-indigo-500 focus:ring focus:ring-indigo-100 bg-white">
                                        <p class="text-[10px] text-gray-400 mt-1">Accepts: JPEG, PNG, JPG, GIF, WEBP. Max size: 2MB.</p>
                                        @error('thumbnail')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @elseif($fieldKey === 'script_file')
                                    <!-- Script Document -->
                                    <div class="mb-6">
                                        <label for="script_file" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Script Document @if($required)<span class="text-rose-500 font-bold">*</span>@endif
                                        </label>
                                        @if($task->script_path)
                                            <div class="mb-3 flex items-center gap-3 bg-slate-50 p-2.5 rounded-lg border border-gray-100">
                                                <svg class="w-8 h-8 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                <div class="truncate">
                                                    <p class="text-[10px] text-gray-400 font-bold uppercase">Current Script</p>
                                                    <a href="{{ asset('storage/' . $task->script_path) }}" download class="text-xs text-indigo-600 hover:underline truncate max-w-[150px] block">Download Document</a>
                                                </div>
                                            </div>
                                        @endif
                                        <input type="file" name="script_file" id="script_file" {{ $requiredAttr }} accept=".docx,.pdf,.txt,.doc" 
                                            class="w-full border border-gray-200 rounded-lg text-xs p-2 focus:border-indigo-500 focus:ring focus:ring-indigo-100 bg-white">
                                        <p class="text-[10px] text-gray-400 mt-1">Accepts: DOCX, PDF, TXT, DOC. Max size: 5MB.</p>
                                        @error('script_file')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif
                            @endif
                        @endforeach

                        <!-- Dynamic Staff Assignments (Always visible at the end of fields) -->
                        @if($activeColumns->isNotEmpty())
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                @foreach($activeColumns as $col)
                                    <div>
                                        <label for="staff_{{ $col->id }}" class="block text-sm font-semibold text-gray-700 mb-2">{{ $col->name }} (Optional)</label>
                                        <select name="staff_assignments[{{ $col->id }}]" id="staff_{{ $col->id }}" 
                                            class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                            <option value="">Select Staff</option>
                                            @foreach($usersByDesignation[$col->id] ?? $users as $staff)
                                                <option value="{{ $staff->id }}" 
                                                    {{ (isset($assignments[$col->id]) && $assignments[$col->id] == $staff->id) ? 'selected' : '' }}>
                                                    {{ $staff->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('staff_assignments.' . $col->id)
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Submit Button -->
                        <div class="flex justify-between items-center">
                            <a href="{{ route('tasks.index') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-700">Cancel</a>
                            <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition ease-in-out duration-150">
                                UPDATE TASK
                            </button>
                        </div>

                    </form>

                    <!-- Comments & Collaboration Loop -->
                    <div id="comments-loop" class="mt-12 border-t border-gray-100 pt-8 scroll-mt-6">
                        <h3 class="font-bold text-gray-800 text-lg mb-6 flex items-center">
                            <svg class="w-5 h-5 text-indigo-500 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                            Comments & Feedback Loop
                        </h3>

                        <!-- Add Comment Form -->
                        <form action="{{ route('tasks.comments.store', $task->id) }}" method="POST" class="mb-6">
                            @csrf
                            <div class="mb-3">
                                <textarea name="content" rows="3" required placeholder="Write feedback, update status, or leave a note..." 
                                    class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100"></textarea>
                                @error('content')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-1.5 px-4 rounded-lg shadow text-xs transition duration-150">
                                    Post Comment
                                </button>
                            </div>
                        </form>

                        <!-- Comments List -->
                        <div id="comments-list-container" class="space-y-4 max-h-96 overflow-y-auto pr-1">
                            @php
                                $comments = $task->comments()->with('user')->orderBy('created_at', 'desc')->get();
                            @endphp
                            @forelse($comments as $comment)
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 flex flex-col gap-2">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-800 text-sm">{{ $comment->user?->name ?? 'System' }}</span>
                                            @php
                                                $commentRole = $comment->user?->role ?? 'staff';
                                            @endphp
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $commentRole === 'admin' ? 'bg-purple-100 text-purple-800' : ($commentRole === 'team_leader' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                                                {{ ucfirst($commentRole) }}
                                            </span>
                                        </div>
                                        <span class="text-xs text-gray-400 font-semibold">{{ $comment->created_at?->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $comment->content }}</p>
                                </div>
                            @empty
                                <div id="no-comments-placeholder" class="text-center py-6 text-xs text-gray-400">
                                    No comments yet. Start the conversation!
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const titleInput = document.getElementById('title');
        const titleLoading = document.getElementById('title_loading') || createLoadingIndicator();
        
        function createLoadingIndicator() {
            const label = document.querySelector('label[for="title"]');
            if (label) {
                const span = document.createElement('span');
                span.id = 'title_loading';
                span.className = 'text-xs text-indigo-500 font-semibold hidden animate-pulse ms-2';
                span.innerText = '(Fetching title...)';
                label.appendChild(span);
                return span;
            }
            return null;
        }

        function fetchMeta(url, previewDivId, platformName) {
            if (!url) return;
            
            // Show loading indicator
            if (titleLoading) titleLoading.classList.remove('hidden');
            
            const previewDiv = document.getElementById(previewDivId);
            const previewImg = previewDiv ? previewDiv.querySelector('img') : null;
            
            fetch(`/tasks/fetch-metadata?url=${encodeURIComponent(url)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Populate title if currently empty or not touched
                        if (titleInput && (!titleInput.value || titleInput.value.trim() === '')) {
                            titleInput.value = data.title;
                        }
                        // Show thumbnail preview
                        if (previewDiv && previewImg && data.thumbnail_url) {
                            previewImg.src = data.thumbnail_url;
                            previewDiv.classList.remove('hidden');
                        }
                    }
                })
                .catch(err => console.error('Error fetching metadata:', err))
                .finally(() => {
                    if (titleLoading) titleLoading.classList.add('hidden');
                });
        }

        const fbInput = document.getElementById('facebook_url');
        if (fbInput) {
            // Trigger check if there is an initial value on page load
            if (fbInput.value) {
                fetchMeta(fbInput.value, 'fb_thumbnail_preview', 'Facebook');
            }
            fbInput.addEventListener('change', function () {
                fetchMeta(fbInput.value, 'fb_thumbnail_preview', 'Facebook');
            });
            fbInput.addEventListener('paste', function (e) {
                setTimeout(() => {
                    fetchMeta(fbInput.value, 'fb_thumbnail_preview', 'Facebook');
                }, 100);
            });
        }

        const ytInput = document.getElementById('youtube_url');
        if (ytInput) {
            // Trigger check if there is an initial value on page load
            if (ytInput.value) {
                fetchMeta(ytInput.value, 'yt_thumbnail_preview', 'YouTube');
            }
            ytInput.addEventListener('change', function () {
                fetchMeta(ytInput.value, 'yt_thumbnail_preview', 'YouTube');
            });
            ytInput.addEventListener('paste', function (e) {
                setTimeout(() => {
                    fetchMeta(ytInput.value, 'yt_thumbnail_preview', 'YouTube');
                }, 100);
            });
        }
    });
    </script>
</x-app-layout>

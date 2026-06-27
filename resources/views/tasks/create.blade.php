<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create New Task') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 flex flex-col md:flex-row">
                
                <!-- Left Info Panel -->
                <div class="bg-gray-50 p-6 md:w-1/4 border-b md:border-b-0 md:border-e border-gray-100">
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Create New Task</h3>
                    <p class="text-sm text-gray-500">Always ensure correct fields and links before save.</p>
                </div>

                <!-- Form Panel -->
                <div class="p-8 md:w-3/4">
                    <form action="{{ route('tasks.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Title -->
                        <div class="mb-6">
                            <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Title</label>
                            <input type="text" name="title" id="title" required placeholder="Enter task title..." 
                                class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                            @error('title')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Task Type (Radio Buttons) -->
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Task Type</label>
                            <div class="flex flex-wrap gap-4 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                @forelse($taskTypes as $type)
                                    <label class="inline-flex items-center text-sm text-gray-700 cursor-pointer">
                                        <input type="radio" name="task_type_id" value="{{ $type->id }}" required class="text-indigo-600 focus:ring-indigo-500">
                                        <span class="ms-2 px-2 py-0.5 rounded text-xs font-semibold" style="background-color: {{ $type->color ?? '#e2e8f0' }}">
                                            {{ $type->name }}
                                        </span>
                                    </label>
                                @empty
                                    <p class="text-xs text-gray-400">No task types configured. Please configure task types in <a href="{{ route('settings.index') }}" class="text-indigo-600 underline">settings</a> first.</p>
                                @endforelse
                            </div>
                            @error('task_type_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Facebook URL -->
                        <div class="mb-6">
                            <label for="facebook_url" class="block text-sm font-semibold text-gray-700 mb-2">Facebook URL</label>
                            <input type="url" name="facebook_url" id="facebook_url" placeholder="https://facebook.com/..." 
                                class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                            <div id="fb_thumbnail_preview" class="mt-2 hidden">
                                <p class="text-xs font-bold text-gray-500 mb-1">Facebook Video Preview:</p>
                                <img src="" class="h-20 rounded border shadow-sm">
                            </div>
                            @error('facebook_url')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Select Facebook Page (Radio list with clear) -->
                        <div class="mb-6" x-data="{ selectedPage: '' }">
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-sm font-semibold text-gray-700">Select Facebook Page</label>
                                <button type="button" @click="selectedPage = ''; $refs.fbPageRadios.querySelectorAll('input').forEach(i => i.checked = false)" class="text-xs text-indigo-500 hover:text-indigo-700 font-semibold">clear</button>
                            </div>
                            <div ref="fbPageRadios" class="flex flex-wrap gap-3 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                @forelse($facebookPages as $page)
                                    <label class="inline-flex items-center text-xs text-gray-600 cursor-pointer">
                                        <input type="radio" name="facebook_page_id" value="{{ $page->id }}" x-model="selectedPage" class="text-indigo-600 focus:ring-indigo-500">
                                        <span class="ms-1.5">{{ $page->name }}</span>
                                    </label>
                                @empty
                                    <p class="text-xs text-gray-400">No Facebook pages configured.</p>
                                @endforelse
                            </div>
                            @error('facebook_page_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Youtube URL -->
                        <div class="mb-6">
                            <label for="youtube_url" class="block text-sm font-semibold text-gray-700 mb-2">Youtube URL</label>
                            <input type="url" name="youtube_url" id="youtube_url" placeholder="https://youtube.com/..." 
                                class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                            <div id="yt_thumbnail_preview" class="mt-2 hidden">
                                <p class="text-xs font-bold text-gray-500 mb-1">YouTube Video Preview:</p>
                                <img src="" class="h-20 rounded border shadow-sm">
                            </div>
                            @error('youtube_url')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Select Youtube Channel (Radio list with clear) -->
                        <div class="mb-6" x-data="{ selectedChannel: '' }">
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-sm font-semibold text-gray-700">Select Youtube Channel</label>
                                <button type="button" @click="selectedChannel = ''; $refs.ytChannelRadios.querySelectorAll('input').forEach(i => i.checked = false)" class="text-xs text-indigo-500 hover:text-indigo-700 font-semibold">clear</button>
                            </div>
                            <div ref="ytChannelRadios" class="flex flex-wrap gap-3 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                @forelse($youtubeChannels as $chan)
                                    <label class="inline-flex items-center text-xs text-gray-600 cursor-pointer">
                                        <input type="radio" name="youtube_channel_id" value="{{ $chan->id }}" x-model="selectedChannel" class="text-indigo-600 focus:ring-indigo-500">
                                        <span class="ms-1.5">{{ $chan->name }}</span>
                                    </label>
                                @empty
                                    <p class="text-xs text-gray-400">No YouTube channels configured.</p>
                                @endforelse
                            </div>
                            @error('youtube_channel_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- On Air Time and Date -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="on_air_time" class="block text-sm font-semibold text-gray-700 mb-2">On Air Time</label>
                                <input type="text" name="on_air_time" id="on_air_time" required placeholder="e.g. 03:00 PM" value="{{ date('h:i A') }}"
                                    class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                @error('on_air_time')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="on_air_date" class="block text-sm font-semibold text-gray-700 mb-2">On Air Date</label>
                                <input type="date" name="on_air_date" id="on_air_date" required value="{{ date('Y-m-d') }}"
                                    class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                @error('on_air_date')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Area / News Source -->
                        <div class="mb-6">
                            <label for="area_id" class="block text-sm font-semibold text-gray-700 mb-2">Area</label>
                            <select name="area_id" id="area_id" 
                                class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                <option value="">Select Area</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}">{{ $area->name }}</option>
                                @endforeach
                            </select>
                            @error('area_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Team Assignment -->
                        <div class="mb-6">
                            <label for="team_id" class="block text-sm font-semibold text-gray-700 mb-2">Team Assignment</label>
                            <select name="team_id" id="team_id" 
                                class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                <option value="">Select Team</option>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                                @endforeach
                            </select>
                            @error('team_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Shift Assignment -->
                        <div class="mb-6">
                            <label for="shift_id" class="block text-sm font-semibold text-gray-700 mb-2">Shift</label>
                            <select name="shift_id" id="shift_id" 
                                class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                <option value="">Select Shift</option>
                                @foreach($shifts as $shift)
                                    <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                                @endforeach
                            </select>
                            @error('shift_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Staff Roles (Script, Voice, Video Edit) -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div>
                                <label for="script_by" class="block text-sm font-semibold text-gray-700 mb-2">Script (Optional)</label>
                                <select name="script_by" id="script_by" 
                                    class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                    <option value="">Select Staff</option>
                                    @foreach($scriptWriterCandidates as $staff)
                                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                    @endforeach
                                </select>
                                @error('script_by')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="voice_by" class="block text-sm font-semibold text-gray-700 mb-2">Voice (Optional)</label>
                                <select name="voice_by" id="voice_by" 
                                    class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                    <option value="">Select Staff</option>
                                    @foreach($voiceArtistCandidates as $staff)
                                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                    @endforeach
                                </select>
                                @error('voice_by')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="video_edit_by" class="block text-sm font-semibold text-gray-700 mb-2">Video Edit (Optional)</label>
                                <select name="video_edit_by" id="video_edit_by" 
                                    class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                    <option value="">Select Staff</option>
                                    @foreach($videoEditorCandidates as $staff)
                                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                    @endforeach
                                </select>
                                @error('video_edit_by')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Tags -->
                        <div class="mb-6">
                            <label for="tags" class="block text-sm font-semibold text-gray-700 mb-2">Tags (Optional)</label>
                            <input type="text" name="tags" id="tags" placeholder="e.g. Nagad, election" 
                                class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                            @error('tags')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Remarks -->
                        <div class="mb-6">
                            <label for="remarks" class="block text-sm font-semibold text-gray-700 mb-2">Remarks</label>
                            <textarea name="remarks" id="remarks" rows="4" placeholder="Enter remarks..." 
                                class="w-full border-gray-200 rounded-lg text-sm p-2.5 focus:border-indigo-500 focus:ring focus:ring-indigo-100"></textarea>
                            @error('remarks')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Media File Uploads -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="thumbnail" class="block text-sm font-semibold text-gray-700 mb-2">Thumbnail Upload (Optional)</label>
                                <input type="file" name="thumbnail" id="thumbnail" accept="image/*" 
                                    class="w-full border border-gray-200 rounded-lg text-xs p-2 focus:border-indigo-500 focus:ring focus:ring-indigo-100 bg-white">
                                <p class="text-[10px] text-gray-400 mt-1">Accepts: JPEG, PNG, JPG, GIF, WEBP. Max size: 2MB.</p>
                                @error('thumbnail')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="script_file" class="block text-sm font-semibold text-gray-700 mb-2">Script Document (Optional)</label>
                                <input type="file" name="script_file" id="script_file" accept=".docx,.pdf,.txt,.doc" 
                                    class="w-full border border-gray-200 rounded-lg text-xs p-2 focus:border-indigo-500 focus:ring focus:ring-indigo-100 bg-white">
                                <p class="text-[10px] text-gray-400 mt-1">Accepts: DOCX, PDF, TXT, DOC. Max size: 5MB.</p>
                                @error('script_file')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition ease-in-out duration-150">
                                SAVE
                            </button>
                        </div>

                    </form>
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

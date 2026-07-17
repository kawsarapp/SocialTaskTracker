<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dynamic Configuration Panel') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Flash Success Message -->
            @if(session('success'))
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg flex items-center shadow-sm">
                    <svg class="w-5 h-5 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Side: User/Staff Creation Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6 self-start">
                    <h3 class="text-base font-bold text-gray-800 mb-6 flex items-center">
                        <svg class="w-5 h-5 text-indigo-500 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Add New Staff Member
                    </h3>
                    <form action="{{ route('settings.users.store') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="user_name" class="block text-sm font-semibold text-gray-600 mb-2">Name</label>
                            <input type="text" name="name" id="user_name" required placeholder="Full Name" 
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                        </div>

                        <div class="mb-4">
                            <label for="user_email" class="block text-sm font-semibold text-gray-600 mb-2">Email Address</label>
                            <input type="email" name="email" id="user_email" required placeholder="staff@company.com" 
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                        </div>

                        <div class="mb-4">
                            <label for="user_password" class="block text-sm font-semibold text-gray-600 mb-2">Default Password</label>
                            <input type="text" name="password" id="user_password" required value="password123" 
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                        </div>

                        <div class="mb-4">
                            <label for="user_role" class="block text-sm font-semibold text-gray-600 mb-2">Role</label>
                            <select name="role" id="user_role" required 
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                <option value="staff">Staff (Standard Entry)</option>
                                <option value="team_leader">Team Leader</option>
                                <option value="admin">Tenant Admin</option>
                            </select>
                        </div>

                        <div class="mb-6">
                            <label for="user_designation" class="block text-sm font-semibold text-gray-600 mb-2">Designation / Role (Optional)</label>
                            <select name="designation_id" id="user_designation" 
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                <option value="">Select Designation / Role</option>
                                @foreach($designations as $desg)
                                    <option value="{{ $desg->id }}">{{ $desg->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow text-sm transition duration-150">
                            Create User Account
                        </button>
                    </form>

                    <!-- Staff List -->
                    <div class="mt-8 border-t border-gray-100 pt-6">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Team Roster</h4>
                        <div class="space-y-3 max-h-60 overflow-y-auto">
                            @foreach($users as $u)
                                <div class="flex justify-between items-center bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                                    <div>
                                        <div class="text-sm font-bold text-gray-800">{{ $u->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $u->email }}</div>
                                    </div>
                                    <div class="flex flex-col gap-1 items-end">
                                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $u->role === 'admin' ? 'bg-purple-100 text-purple-800' : ($u->role === 'team_leader' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                                            {{ ucfirst($u->role) }}
                                        </span>
                                        @if($u->designation)
                                            <span class="inline-flex px-2 py-0.5 text-[10px] font-bold rounded" style="background-color: {{ $u->designation->color }}; color: #1e293b;">
                                                {{ $u->designation->name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                    </div>
                </div>

                <!-- Messaging Webhook Settings Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6 mt-8">
                    <h3 class="text-base font-bold text-gray-800 mb-2 flex items-center">
                        <svg class="w-5 h-5 text-indigo-500 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        Messaging Webhooks
                    </h3>
                    <p class="text-xs text-gray-400 mb-6">Receive real-time automated chat updates on Slack and Telegram when content goes live.</p>
                    
                    <form action="{{ route('settings.webhooks.update') }}" method="POST" class="space-y-4">
                        @csrf
                        <!-- Slack Webhook -->
                        <div>
                            <label for="slack_webhook_url" class="block text-xs font-bold text-gray-500 mb-1">Slack Webhook URL</label>
                            <input type="url" name="slack_webhook_url" id="slack_webhook_url" 
                                value="{{ old('slack_webhook_url', Auth::user()->tenant?->slack_webhook_url) }}"
                                placeholder="https://hooks.slack.com/services/..." 
                                class="w-full border-gray-200 rounded-lg text-xs focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                            @error('slack_webhook_url')
                                <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Telegram Bot Token -->
                        <div>
                            <label for="telegram_bot_token" class="block text-xs font-bold text-gray-500 mb-1">Telegram Bot Token</label>
                            <input type="text" name="telegram_bot_token" id="telegram_bot_token" 
                                value="{{ old('telegram_bot_token', Auth::user()->tenant?->telegram_bot_token) }}"
                                placeholder="e.g. 123456789:ABCdefGhI..." 
                                class="w-full border-gray-200 rounded-lg text-xs focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                            @error('telegram_bot_token')
                                <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Telegram Chat ID -->
                        <div>
                            <label for="telegram_chat_id" class="block text-xs font-bold text-gray-500 mb-1">Telegram Chat ID</label>
                            <input type="text" name="telegram_chat_id" id="telegram_chat_id" 
                                value="{{ old('telegram_chat_id', Auth::user()->tenant?->telegram_chat_id) }}"
                                placeholder="e.g. -100123456789" 
                                class="w-full border-gray-200 rounded-lg text-xs focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                            @error('telegram_chat_id')
                                <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 rounded-lg text-xs shadow transition">
                            Save Webhook Settings
                        </button>
                    </form>
                </div>

                <!-- API Integration Settings Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6 mt-8">
                    <h3 class="text-base font-bold text-gray-800 mb-2 flex items-center">
                        <svg class="w-5 h-5 text-indigo-500 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                        Social API Integration
                    </h3>
                    <p class="text-xs text-gray-400 mb-6">Auto-sync video views, likes, and reach on YouTube and Facebook page clips.</p>
                    
                    <form action="{{ route('settings.api-keys.update') }}" method="POST" class="space-y-4">
                        @csrf
                        <!-- YouTube API Key -->
                        <div>
                            <label for="youtube_api_key" class="block text-xs font-bold text-gray-500 mb-1">YouTube API Key</label>
                            <input type="text" name="youtube_api_key" id="youtube_api_key" 
                                value="{{ old('youtube_api_key', Auth::user()->tenant?->youtube_api_key) }}"
                                placeholder="AIzaSy..." 
                                class="w-full border-gray-200 rounded-lg text-xs focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                            @error('youtube_api_key')
                                <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Facebook Page Access Token -->
                        <div>
                            <label for="facebook_page_access_token" class="block text-xs font-bold text-gray-500 mb-1">Facebook Page Access Token</label>
                            <textarea name="facebook_page_access_token" id="facebook_page_access_token" rows="3"
                                placeholder="EAAG..." 
                                class="w-full border-gray-200 rounded-lg text-xs focus:border-indigo-500 focus:ring focus:ring-indigo-100">{{ old('facebook_page_access_token', Auth::user()->tenant?->facebook_page_access_token) }}</textarea>
                            @error('facebook_page_access_token')
                                <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Sync Interval Dropdown -->
                        <div>
                            <label for="api_sync_interval" class="block text-xs font-bold text-gray-500 mb-1">Auto Sync Frequency (Quota Guard)</label>
                            <select name="api_sync_interval" id="api_sync_interval" 
                                class="w-full border-gray-200 rounded-lg text-xs focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                @php
                                    $interval = Auth::user()->tenant?->api_sync_interval ?? 0;
                                @endphp
                                <option value="0" {{ $interval == 0 ? 'selected' : '' }}>Manual Sync Only (Recommended to save quota)</option>
                                <option value="6" {{ $interval == 6 ? 'selected' : '' }}>Every 6 Hours</option>
                                <option value="12" {{ $interval == 12 ? 'selected' : '' }}>Every 12 Hours</option>
                                <option value="24" {{ $interval == 24 ? 'selected' : '' }}>Every 24 Hours</option>
                            </select>
                            @error('api_sync_interval')
                                <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 rounded-lg text-xs shadow transition">
                                Save API Keys
                            </button>
                        </div>
                    </form>

                    <!-- Manual Bulk Sync Form -->
                    @if(Auth::user()->tenant?->youtube_api_key || Auth::user()->tenant?->facebook_page_access_token)
                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">On-Demand Bulk Sync</h4>
                            <p class="text-[11px] text-gray-400 mb-4">Trigger an immediate manual update of stats for all tasks logged in the last 7 days.</p>
                            
                            @if(Auth::user()->tenant?->last_api_sync_at)
                                <div class="mb-3 text-[11px] text-gray-500 flex items-center bg-gray-50 p-2 rounded border border-gray-100">
                                    <svg class="w-3.5 h-3.5 text-gray-400 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Last Bulk Sync: {{ Auth::user()->tenant->last_api_sync_at->format('d M Y h:i A') }}
                                </div>
                            @endif

                            <form action="{{ route('settings.api-keys.sync') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 rounded-lg text-xs shadow transition flex items-center justify-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18v3z"></path></svg>
                                    Sync All Tasks Now
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

                <!-- Right Side: Config Cards (Dynamic Fields) -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <!-- Row 1: Task Types & Teams -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Task Types -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="font-bold text-gray-800 text-base">Task Types (e.g. LIVE, PKG)</h3>
                            </div>
                            <form action="{{ route('settings.task-types.store') }}" method="POST" class="flex gap-2 mb-4">
                                @csrf
                                <input type="text" name="name" required placeholder="Type Name" class="flex-1 border-gray-200 rounded-lg text-sm focus:ring focus:ring-indigo-100">
                                <input type="color" name="color" value="#fee2e2" class="w-10 h-9 p-0 border border-gray-200 rounded-lg cursor-pointer">
                                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Add</button>
                            </form>
                            <div class="flex flex-wrap gap-2 max-h-60 overflow-y-auto">
                                @forelse($taskTypes as $t)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border border-gray-200 shadow-sm" style="background-color: {{ $t->color ?? '#e2e8f0' }}; color: #1e293b;">
                                        {{ $t->name }}
                                        <form action="{{ route('settings.task-types.destroy', $t->id) }}" method="POST" class="inline ms-1.5">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-500 font-bold">&times;</button>
                                        </form>
                                    </span>
                                @empty
                                    <p class="text-xs text-gray-400">No task types configured.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Teams -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="font-bold text-gray-800 text-base">Teams</h3>
                            </div>
                            <form action="{{ route('settings.teams.store') }}" method="POST" class="flex gap-2 mb-4">
                                @csrf
                                <input type="text" name="name" required placeholder="Team Name" class="flex-1 border-gray-200 rounded-lg text-sm focus:ring focus:ring-indigo-100">
                                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Add</button>
                            </form>
                            <div class="space-y-2 max-h-60 overflow-y-auto">
                                @forelse($teams as $team)
                                    <div class="flex justify-between items-center bg-gray-50 px-3 py-2 rounded-lg border border-gray-100">
                                        <span class="text-sm font-semibold text-gray-700">{{ $team->name }}</span>
                                        <form action="{{ route('settings.teams.destroy', $team->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-500 text-sm font-bold">&times;</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400">No teams configured.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Facebook Pages & YouTube Channels -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Facebook Pages -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                            <h3 class="font-bold text-gray-800 text-base mb-6">Facebook Pages</h3>
                            <form action="{{ route('settings.fb-pages.store') }}" method="POST" class="flex gap-2 mb-4">
                                @csrf
                                <input type="text" name="name" required placeholder="Page URL or Name" class="flex-1 border-gray-200 rounded-lg text-sm focus:ring focus:ring-indigo-100">
                                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Add</button>
                            </form>
                            <div class="space-y-2 max-h-60 overflow-y-auto">
                                @forelse($facebookPages as $page)
                                    <div class="flex justify-between items-center bg-gray-50 px-3 py-2 rounded-lg border border-gray-100">
                                        <span class="text-sm font-semibold text-gray-700">{{ $page->name }}</span>
                                        <form action="{{ route('settings.fb-pages.destroy', $page->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-500 text-sm font-bold">&times;</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400">No Facebook pages configured.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- YouTube Channels -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                            <h3 class="font-bold text-gray-800 text-base mb-6">YouTube Channels</h3>
                            <form action="{{ route('settings.yt-channels.store') }}" method="POST" class="flex gap-2 mb-4">
                                @csrf
                                <input type="text" name="name" required placeholder="Channel Name" class="flex-1 border-gray-200 rounded-lg text-sm focus:ring focus:ring-indigo-100">
                                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Add</button>
                            </form>
                            <div class="space-y-2 max-h-60 overflow-y-auto">
                                @forelse($youtubeChannels as $chan)
                                    <div class="flex justify-between items-center bg-gray-50 px-3 py-2 rounded-lg border border-gray-100">
                                        <span class="text-sm font-semibold text-gray-700">{{ $chan->name }}</span>
                                        <form action="{{ route('settings.yt-channels.destroy', $chan->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-500 text-sm font-bold">&times;</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400">No YouTube channels configured.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Areas & Shifts -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Areas -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                            <h3 class="font-bold text-gray-800 text-base mb-6">Areas / News Sources</h3>
                            <form action="{{ route('settings.areas.store') }}" method="POST" class="flex gap-2 mb-4">
                                @csrf
                                <input type="text" name="name" required placeholder="Area/Source Name" class="flex-1 border-gray-200 rounded-lg text-sm focus:ring focus:ring-indigo-100">
                                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Add</button>
                            </form>
                            <div class="space-y-2 max-h-60 overflow-y-auto">
                                @forelse($areas as $area)
                                    <div class="flex justify-between items-center bg-gray-50 px-3 py-2 rounded-lg border border-gray-100">
                                        <span class="text-sm font-semibold text-gray-700">{{ $area->name }}</span>
                                        <form action="{{ route('settings.areas.destroy', $area->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-500 text-sm font-bold">&times;</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400">No areas configured.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Shifts -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                            <h3 class="font-bold text-gray-800 text-base mb-6">Shifts</h3>
                            <form action="{{ route('settings.shifts.store') }}" method="POST" class="flex gap-2 mb-4">
                                @csrf
                                <input type="text" name="name" required placeholder="Shift Name (e.g. Morning)" class="flex-1 border-gray-200 rounded-lg text-sm focus:ring focus:ring-indigo-100">
                                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Add</button>
                            </form>
                            <div class="space-y-2 max-h-60 overflow-y-auto">
                                @forelse($shifts as $shift)
                                    <div class="flex justify-between items-center bg-gray-50 px-3 py-2 rounded-lg border border-gray-100">
                                        <span class="text-sm font-semibold text-gray-700">{{ $shift->name }}</span>
                                        <form action="{{ route('settings.shifts.destroy', $shift->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-500 text-sm font-bold">&times;</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400">No shifts configured.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Row 4: Designations -->
                    <div class="grid grid-cols-1 gap-8" x-data="{ permissionsModalOpen: false, selectedDesg: { id: null, name: '', permissions: [] }, openPermissionsModal(desg) { this.selectedDesg = desg; this.permissionsModalOpen = true; } }">
                        <!-- Designations -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                            <h3 class="font-bold text-gray-800 text-base mb-2">Staff Designations / Roles</h3>
                            <p class="text-xs text-gray-400 mb-6">Manage professional positions and roles for your media organization (e.g. Reporter, Script Writer, SEO Specialist).</p>
                            
                            <form action="{{ route('settings.designations.store') }}" method="POST" class="flex gap-4 mb-6 bg-slate-50 p-4 rounded-xl border border-slate-100 max-w-xl">
                                @csrf
                                <div class="flex-1">
                                    <label for="desg_name" class="block text-xs font-bold text-gray-500 mb-1">Designation Name</label>
                                    <input type="text" name="name" id="desg_name" required placeholder="e.g. SEO Specialist" class="w-full border-gray-200 rounded-lg text-xs focus:ring focus:ring-indigo-100 bg-white">
                                </div>
                                <div>
                                    <label for="desg_color" class="block text-xs font-bold text-gray-500 mb-1">Badge Color</label>
                                    <div class="flex gap-2 items-center">
                                        <input type="color" name="color" id="desg_color" value="#e2e8f0" class="w-10 h-8 p-0 border border-gray-200 rounded-lg cursor-pointer bg-white">
                                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-indigo-700 transition">Add Designation</button>
                                    </div>
                                </div>
                            </form>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr class="border-b border-gray-100 text-gray-400 uppercase font-bold tracking-wider">
                                            <th class="pb-3 font-semibold">Designation</th>
                                            <th class="pb-3 font-semibold text-center">Include as Task Column</th>
                                            <th class="pb-3 font-semibold">Assigned Permissions</th>
                                            <th class="pb-3 font-semibold text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @forelse($designations as $desg)
                                            <tr>
                                                <td class="py-3">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold border border-gray-200 shadow-sm" style="background-color: {{ $desg->color ?? '#e2e8f0' }}; color: #1e293b;">
                                                        {{ $desg->name }}
                                                    </span>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <form action="{{ route('settings.designations.toggle-column', $desg->id) }}" method="POST" class="inline">
                                                        @csrf @method('PUT')
                                                        <button type="submit" class="px-2 py-1 rounded text-[10px] font-bold shadow-sm transition {{ $desg->is_task_column ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}" title="Toggle visibility as a dashboard task column">
                                                            {{ $desg->is_task_column ? 'Column: Active' : 'Column: Hidden' }}
                                                        </button>
                                                    </form>
                                                </td>
                                                <td class="py-3 text-gray-500">
                                                    @php
                                                        $perms = $desg->permissions ?? [];
                                                    @endphp
                                                    @if(empty($perms))
                                                        <span class="text-gray-400 italic text-[10px]">No permissions assigned (View only)</span>
                                                    @else
                                                        <div class="flex flex-wrap gap-1">
                                                            @foreach($perms as $p)
                                                                <span class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-700 text-[10px] font-semibold">
                                                                    {{ str_replace('_', ' ', $p) }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="py-3 text-right">
                                                    <div class="inline-flex items-center gap-2">
                                                        <!-- Edit Permissions Button -->
                                                        <button type="button" 
                                                            @click="openPermissionsModal({{ json_encode($desg) }})"
                                                            class="text-indigo-600 hover:text-indigo-900 font-bold p-1 rounded hover:bg-indigo-50 transition" 
                                                            title="Edit Permissions">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                                        </button>

                                                        <!-- Delete Form -->
                                                        <form action="{{ route('settings.designations.destroy', $desg->id) }}" method="POST" class="inline">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" 
                                                                class="text-red-500 hover:text-red-800 font-bold p-1 rounded hover:bg-red-50 transition" 
                                                                onclick="return confirm('Are you sure you want to delete this designation?')">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="py-4 text-center text-gray-400 italic">No designations configured.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Permissions Modal Overlay -->
                        <div x-show="permissionsModalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-black bg-opacity-50 p-4" x-cloak>
                            <div class="bg-white rounded-xl shadow-xl max-w-md w-full border border-gray-100 p-6 relative" @click.away="permissionsModalOpen = false">
                                <button type="button" @click="permissionsModalOpen = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 font-bold text-lg">&times;</button>
                                
                                <h3 class="font-bold text-gray-800 text-lg mb-2">Edit Designation Permissions</h3>
                                <p class="text-xs text-gray-400 mb-6">Configure what members with the designation <span class="font-bold text-indigo-600" x-text="selectedDesg.name"></span> can see and do.</p>
                                
                                <form :action="`/settings/designations/${selectedDesg.id}/permissions`" method="POST" class="space-y-4">
                                    @csrf
                                    @method('PUT')
                                    
                                    <div class="space-y-3">
                                        <!-- View Dashboard -->
                                        <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition">
                                            <input type="checkbox" name="permissions[]" value="view_tasks" :checked="selectedDesg.permissions && selectedDesg.permissions.includes('view_tasks')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1">
                                            <div>
                                                <span class="block text-sm font-semibold text-gray-700">View Tasks / Dashboard</span>
                                                <span class="block text-xs text-gray-400">Can view the tasks log lists, tables and calendars.</span>
                                            </div>
                                        </label>

                                        <!-- Log Task -->
                                        <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition">
                                            <input type="checkbox" name="permissions[]" value="create_tasks" :checked="selectedDesg.permissions && selectedDesg.permissions.includes('create_tasks')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1">
                                            <div>
                                                <span class="block text-sm font-semibold text-gray-700">Log/Create Tasks</span>
                                                <span class="block text-xs text-gray-400">Can log new tasks and organization assignments.</span>
                                            </div>
                                        </label>

                                        <!-- Edit All Tasks -->
                                        <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition">
                                            <input type="checkbox" name="permissions[]" value="edit_all_tasks" :checked="selectedDesg.permissions && selectedDesg.permissions.includes('edit_all_tasks')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1">
                                            <div>
                                                <span class="block text-sm font-semibold text-gray-700">Edit All Tasks</span>
                                                <span class="block text-xs text-gray-400">Can edit any task (otherwise restricted to owned/assigned tasks).</span>
                                            </div>
                                        </label>

                                        <!-- Delete Tasks -->
                                        <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition">
                                            <input type="checkbox" name="permissions[]" value="delete_tasks" :checked="selectedDesg.permissions && selectedDesg.permissions.includes('delete_tasks')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1">
                                            <div>
                                                <span class="block text-sm font-semibold text-gray-700">Delete Tasks</span>
                                                <span class="block text-xs text-gray-400">Can delete tasks from database logs.</span>
                                            </div>
                                        </label>

                                        <!-- View Analytics -->
                                        <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition">
                                            <input type="checkbox" name="permissions[]" value="view_analytics" :checked="selectedDesg.permissions && selectedDesg.permissions.includes('view_analytics')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1">
                                            <div>
                                                <span class="block text-sm font-semibold text-gray-700">View Analytics & Charts</span>
                                                <span class="block text-xs text-gray-400">Can access the visual analytics, performance metrics, and productivity reports.</span>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="flex gap-3 justify-end pt-4 border-t border-gray-100">
                                        <button type="button" @click="permissionsModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-4 rounded-lg text-xs transition">Cancel</button>
                                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg text-xs transition shadow-sm">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Leave & Attendance Management -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                        <h3 class="font-bold text-gray-800 text-base mb-4 flex items-center">
                            <svg class="w-5 h-5 text-indigo-500 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Attendance & Leave Management
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Leave Request Form -->
                            <div>
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Mark Staff on Leave</h4>
                                <form action="{{ route('settings.attendance.leave') }}" method="POST" class="space-y-4 bg-slate-50 p-4 rounded-lg border border-gray-100">
                                    @csrf
                                    <div>
                                        <label for="leave_user_id" class="block text-xs font-bold text-gray-500 mb-1">Select Staff</label>
                                        <select name="user_id" id="leave_user_id" required class="w-full border-gray-200 rounded-lg text-xs bg-white focus:ring focus:ring-indigo-100">
                                            <option value="">Choose Staff</option>
                                            @foreach($users as $staff)
                                                @if($staff->role !== 'super_admin')
                                                    <option value="{{ $staff->id }}">{{ $staff->name }} ({{ ucfirst($staff->role) }})</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="leave_date" class="block text-xs font-bold text-gray-500 mb-1">Date</label>
                                        <input type="date" name="date" id="leave_date" required value="{{ date('Y-m-d') }}" class="w-full border-gray-200 rounded-lg text-xs bg-white focus:ring focus:ring-indigo-100">
                                    </div>
                                    <div>
                                        <label for="leave_remarks" class="block text-xs font-bold text-gray-500 mb-1">Reason / Remarks</label>
                                        <input type="text" name="remarks" id="leave_remarks" required placeholder="e.g. Sick Leave, Annual Leave" class="w-full border-gray-200 rounded-lg text-xs bg-white focus:ring focus:ring-indigo-100">
                                    </div>
                                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 rounded-lg text-xs shadow transition">
                                        Mark as On Leave
                                    </button>
                                </form>
                            </div>

                            <!-- Attendance Roster Today -->
                            <div>
                                @php
                                    $todayAttendances = \App\Models\Attendance::where('date', now()->format('Y-m-d'))->get()->keyBy('user_id');
                                @endphp
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Today's Attendance Roster</h4>
                                <div class="space-y-2 max-h-[220px] overflow-y-auto pr-1">
                                    @forelse($users as $staff)
                                        @if($staff->role !== 'super_admin')
                                            @php
                                                $att = $todayAttendances->get($staff->id);
                                            @endphp
                                            <div class="flex justify-between items-center bg-gray-50 p-2.5 rounded-lg border border-gray-100 text-xs">
                                                <div>
                                                    <span class="font-bold text-gray-800">{{ $staff->name }}</span>
                                                    <span class="text-gray-400"> ({{ ucfirst($staff->role) }})</span>
                                                </div>
                                                <div>
                                                    @if($att)
                                                        @if($att->status === 'leave')
                                                            <span class="inline-flex px-2 py-0.5 font-bold rounded bg-amber-50 border border-amber-200 text-amber-800" title="{{ $att->remarks }}">
                                                                🌴 On Leave
                                                            </span>
                                                        @else
                                                            <span class="inline-flex px-2 py-0.5 font-bold rounded bg-emerald-50 border border-emerald-200 text-emerald-800" title="Check-in: {{ $att->check_in ? $att->check_in->format('h:i A') : '-' }}">
                                                                ✓ Present
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="inline-flex px-2 py-0.5 font-bold rounded bg-rose-50 border border-rose-200 text-rose-800">
                                                            ✖ Absent
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @empty
                                        <p class="text-xs text-gray-400">No staff members registered.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    @php
                        $formConfig = Auth::user()->tenant->task_form_config ?? [];
                    @endphp

                    <!-- Task Form Fields Configuration Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6"
                        x-data="{
                            draggedIdx: null,
                            fields: {{ json_encode(array_values($formConfig)) }},
                            newFieldLabel: '',
                            newFieldType: 'text',
                            newFieldRequired: false,
                            addCustomField() {
                                let label = this.newFieldLabel.trim();
                                if (!label) return;
                                let key = 'custom_' + Date.now();
                                this.fields.push({
                                    key: key,
                                    label: label,
                                    type: this.newFieldType,
                                    visible: true,
                                    required: this.newFieldRequired,
                                    sort_order: this.fields.length + 1,
                                    is_custom: true
                                });
                                this.newFieldLabel = '';
                                this.newFieldType = 'text';
                                this.newFieldRequired = false;
                            },
                            deleteField(key) {
                                this.fields = this.fields.filter(f => f.key !== key);
                                this.fields.forEach((f, i) => f.sort_order = i + 1);
                            }
                        }">
                        <div class="flex flex-wrap justify-between items-start gap-4 mb-4">
                            <div>
                                <h3 class="font-bold text-gray-800 text-base mb-2 flex items-center">
                                    <svg class="w-5 h-5 text-indigo-500 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                    </svg>
                                    Task Entry Form Configuration
                                </h3>
                                <p class="text-xs text-gray-400">Drag rows by handle to reorder. Toggle visibility or make fields mandatory. Add custom fields as needed.</p>
                            </div>
                        </div>

                        <form action="{{ route('settings.task-form-config.update') }}" method="POST">
                            @csrf
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr class="border-b border-gray-100 text-gray-400 uppercase font-bold tracking-wider">
                                            <th class="pb-3 text-center w-12 font-semibold">Move</th>
                                            <th class="pb-3 font-semibold">Form Field Name / Label</th>
                                            <th class="pb-3 font-semibold">System Key / Type</th>
                                            <th class="pb-3 font-semibold text-center w-32">Show on Form</th>
                                            <th class="pb-3 font-semibold text-center w-40">Make Mandatory (Required)</th>
                                            <th class="pb-3 text-center w-24 font-semibold">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <!-- Fixed Title Field (always required) -->
                                        <tr class="bg-slate-50/50">
                                            <td class="py-3.5 text-center text-gray-300">
                                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                            </td>
                                            <td class="py-3.5">
                                                <span class="font-bold text-gray-800">Task Title</span>
                                                <span class="text-gray-400 block text-[10px]">Core content title identifier</span>
                                            </td>
                                            <td class="py-3.5">
                                                <span class="text-gray-400 font-mono text-[10px]">title</span>
                                            </td>
                                            <td class="py-3.5 text-center">
                                                <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-500 uppercase">Always Visible</span>
                                            </td>
                                            <td class="py-3.5 text-center">
                                                <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-indigo-50 border border-indigo-100 text-indigo-700 uppercase">Always Required</span>
                                            </td>
                                            <td class="py-3.5 text-center text-gray-400 font-semibold text-[10px]">
                                                System Locked
                                            </td>
                                        </tr>

                                        <!-- Dynamic Fields (Loop through Alpine.js model) -->
                                        <template x-for="(field, index) in fields" :key="field.key">
                                            <tr draggable="true"
                                                @dragstart="draggedIdx = index"
                                                @dragover.prevent
                                                @drop="if (draggedIdx !== null && draggedIdx !== index) {
                                                    let moving = fields.splice(draggedIdx, 1)[0];
                                                    fields.splice(index, 0, moving);
                                                    fields.forEach((f, i) => f.sort_order = i + 1);
                                                    draggedIdx = null;
                                                }"
                                                class="hover:bg-slate-50/80 border-b border-gray-50 transition cursor-move">
                                                
                                                <!-- Drag handle -->
                                                <td class="py-3 px-2 text-center text-gray-400 hover:text-indigo-600">
                                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                                    </svg>
                                                </td>

                                                <!-- Field label (Editable for custom fields) -->
                                                <td class="py-3">
                                                    <template x-if="!field.is_custom">
                                                        <div>
                                                            <span class="font-bold text-gray-800 text-xs" x-text="field.label"></span>
                                                            <input type="hidden" :name="'fields[' + field.key + '][label]'" :value="field.label">
                                                        </div>
                                                    </template>
                                                    <template x-if="field.is_custom">
                                                        <div class="pe-4">
                                                            <input type="text" :name="'fields[' + field.key + '][label]'" x-model="field.label"
                                                                class="w-full text-xs font-bold text-gray-800 border-gray-200 rounded p-1 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                                        </div>
                                                    </template>
                                                </td>

                                                <!-- Field Key / Custom Type description -->
                                                <td class="py-3">
                                                    <template x-if="!field.is_custom">
                                                        <div>
                                                            <span class="text-gray-400 font-mono text-[10px]" x-text="field.key"></span>
                                                            <input type="hidden" :name="'fields[' + field.key + '][type]'" :value="field.type">
                                                            <input type="hidden" :name="'fields[' + field.key + '][is_custom]'" value="0">
                                                        </div>
                                                    </template>
                                                    <template x-if="field.is_custom">
                                                        <div>
                                                            <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 uppercase border border-amber-100" x-text="'Custom (' + field.type + ')'"></span>
                                                            <input type="hidden" :name="'fields[' + field.key + '][type]'" :value="field.type">
                                                            <input type="hidden" :name="'fields[' + field.key + '][is_custom]'" value="1">
                                                        </div>
                                                    </template>
                                                    <!-- Sort Order hidden input -->
                                                    <input type="hidden" :name="'fields[' + field.key + '][sort_order]'" :value="field.sort_order">
                                                </td>

                                                <!-- Show on Form toggle checkbox -->
                                                <td class="py-3 text-center">
                                                    <input type="hidden" :name="'fields[' + field.key + '][visible]'" value="0">
                                                    <input type="checkbox" :name="'fields[' + field.key + '][visible]'" value="1" x-model="field.visible"
                                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                </td>

                                                <!-- Make Mandatory toggle checkbox -->
                                                <td class="py-3 text-center">
                                                    <input type="hidden" :name="'fields[' + field.key + '][required]'" value="0">
                                                    <input type="checkbox" :name="'fields[' + field.key + '][required]'" value="1" x-model="field.required"
                                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                </td>

                                                <!-- Actions (Delete for custom fields, locked icon for system ones) -->
                                                <td class="py-3 text-center text-gray-500">
                                                    <template x-if="!field.is_custom">
                                                        <span class="text-[10px] text-gray-300 font-semibold flex items-center justify-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                                            System
                                                        </span>
                                                    </template>
                                                    <template x-if="field.is_custom">
                                                        <button type="button" @click="deleteField(field.key)" class="text-red-500 hover:text-red-700 transition" title="Delete Custom Field">
                                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                        </button>
                                                    </template>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Add Custom Field Section -->
                            <div class="mt-6 bg-slate-50 p-4 rounded-xl border border-slate-100">
                                <h4 class="font-bold text-gray-700 text-xs mb-3 flex items-center">
                                    <svg class="w-4 h-4 text-indigo-500 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Create New Custom Field
                                </h4>
                                <div class="flex flex-wrap items-end gap-4 text-[11px]">
                                    <div class="flex-1 min-w-[200px]">
                                        <label class="block text-gray-500 font-semibold mb-1">Field Display Label</label>
                                        <input type="text" x-model="newFieldLabel" placeholder="e.g. Video Presenter, Sponsor Name" 
                                            class="w-full text-xs border-gray-200 rounded-lg p-2 focus:ring focus:ring-indigo-100 focus:border-indigo-500 bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-gray-500 font-semibold mb-1">Input Format Type</label>
                                        <select x-model="newFieldType" 
                                            class="text-xs border-gray-200 rounded-lg p-2 focus:ring focus:ring-indigo-100 focus:border-indigo-500 bg-white">
                                            <option value="text">Single Line Text</option>
                                            <option value="number">Numeric Value</option>
                                            <option value="textarea">Paragraph / Multi-line Notes</option>
                                        </select>
                                    </div>
                                    <div class="flex items-center h-9">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox" x-model="newFieldRequired" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="ms-1.5 text-gray-500 font-semibold">Make Mandatory</span>
                                        </label>
                                    </div>
                                    <button type="button" @click="addCustomField" 
                                        class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold py-2 px-4 rounded-lg shadow-sm transition h-9">
                                        + Add Field
                                    </button>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg text-xs shadow-md transition">
                                    Save Configured Layout
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Row 4: Audit Activity Logs -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                        <h3 class="font-bold text-gray-800 text-base mb-2">Audit Logs & Revision History</h3>
                        <p class="text-xs text-gray-400 mb-6">Recent task creations, edits, and deletions logged for accountability.</p>
                        
                        <div class="space-y-4 max-h-96 overflow-y-auto pr-1">
                            @forelse($activityLogs as $log)
                                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 flex flex-col gap-1.5">
                                    <div class="flex justify-between items-start">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-bold text-gray-800 text-sm">
                                                {{ $log->user->name ?? 'System' }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase 
                                                {{ $log->action === 'created' ? 'bg-green-100 text-green-800' : ($log->action === 'updated' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                                {{ $log->action }}
                                            </span>
                                            <span class="text-xs text-gray-500 font-semibold">
                                                Task ID: #{{ $log->task_id }} &ldquo;{{ $log->task_title }}&rdquo;
                                            </span>
                                        </div>
                                        <span class="text-xs text-gray-400 font-semibold">
                                            {{ $log->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                    
                                    @if($log->action === 'updated' && is_array($log->changes))
                                        <div class="mt-1 text-xs space-y-1 bg-white p-3 rounded-lg border border-gray-100">
                                            @foreach($log->changes as $field => $diff)
                                                <div class="flex flex-wrap items-center gap-1.5 text-gray-600">
                                                    <span class="font-semibold text-gray-500">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span>
                                                    <span class="line-through text-red-500 bg-red-50 px-1 rounded">{{ is_scalar($diff['old']) ? $diff['old'] : 'empty' }}</span>
                                                    <span class="text-gray-400">&rarr;</span>
                                                    <span class="text-green-600 font-bold bg-green-50 px-1 rounded">{{ is_scalar($diff['new']) ? $diff['new'] : 'empty' }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center py-8 text-xs text-gray-400">
                                    No activity logs found.
                                </div>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>

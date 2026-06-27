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

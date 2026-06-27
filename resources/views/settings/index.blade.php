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

                        <div class="mb-6">
                            <label for="user_role" class="block text-sm font-semibold text-gray-600 mb-2">Role</label>
                            <select name="role" id="user_role" required 
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                <option value="staff">Staff (Standard Entry)</option>
                                <option value="team_leader">Team Leader</option>
                                <option value="admin">Tenant Admin</option>
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
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $u->role === 'admin' ? 'bg-purple-100 text-purple-800' : ($u->role === 'team_leader' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ ucfirst($u->role) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
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

                    <!-- Row 3: Areas / Locations -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                        <h3 class="font-bold text-gray-800 text-base mb-6">Areas / News Sources</h3>
                        <form action="{{ route('settings.areas.store') }}" method="POST" class="flex gap-2 mb-4">
                            @csrf
                            <input type="text" name="name" required placeholder="Area/Source Name (e.g. Dhaka Office)" class="flex-1 border-gray-200 rounded-lg text-sm focus:ring focus:ring-indigo-100">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Add Area</button>
                        </form>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto pt-2">
                            @forelse($areas as $area)
                                <div class="flex justify-between items-center bg-gray-50 px-3 py-2 rounded-lg border border-gray-100">
                                    <span class="text-sm font-semibold text-gray-700">{{ $area->name }}</span>
                                    <form action="{{ route('settings.areas.destroy', $area->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-500 text-sm font-bold">&times;</button>
                                    </form>
                                </div>
                            @empty
                                <div class="col-span-2 text-center text-xs text-gray-400 py-4">No areas configured.</div>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>

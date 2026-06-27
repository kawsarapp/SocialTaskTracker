<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Super Admin Portal - Tenant Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Flash Messages -->
            @if(session('success'))
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg flex items-center shadow-sm">
                    <svg class="w-5 h-5 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Create Tenant Form -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center">
                        <svg class="w-5 h-5 text-indigo-500 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Create New Organization
                    </h3>
                    <form action="{{ route('superadmin.tenants.store') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-semibold text-gray-600 mb-2">Organization Name</label>
                            <input type="text" name="name" id="name" required placeholder="e.g., DBC News" 
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            @error('name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="slug" class="block text-sm font-semibold text-gray-600 mb-2">Subdomain/Slug</label>
                            <input type="text" name="slug" id="slug" required placeholder="e.g., dbc-news" 
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <p class="text-xs text-gray-400 mt-1">Only lowercase, numbers, and dashes. Unique across the app.</p>
                            @error('slug')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow transition ease-in-out duration-150">
                            Add Tenant
                        </button>
                    </form>
                </div>

                <!-- Tenants List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6 md:col-span-2">
                    <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center">
                        <svg class="w-5 h-5 text-indigo-500 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Active Organizations
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="py-3 px-4 rounded-s-lg">Tenant Name</th>
                                    <th class="py-3 px-4">Slug</th>
                                    <th class="py-3 px-4 text-center">Users</th>
                                    <th class="py-3 px-4 text-center">Tasks</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                    <th class="py-3 px-4 rounded-e-lg text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                                @forelse($tenants as $tenant)
                                    <tr>
                                        <td class="py-4 px-4 font-bold text-gray-900">{{ $tenant->name }}</td>
                                        <td class="py-4 px-4 font-mono text-xs text-gray-500">{{ $tenant->slug }}</td>
                                        <td class="py-4 px-4 text-center font-semibold text-indigo-600">{{ $tenant->users_count }}</td>
                                        <td class="py-4 px-4 text-center font-semibold text-emerald-600">{{ $tenant->tasks_count }}</td>
                                        <td class="py-4 px-4 text-center">
                                            @if($tenant->is_active)
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold text-emerald-800 bg-emerald-100 rounded-full">Active</span>
                                            @else
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">Disabled</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-4 text-center">
                                            <form action="{{ route('superadmin.tenants.toggle', $tenant->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" 
                                                    class="inline-flex items-center text-xs font-semibold px-3 py-1.5 rounded border {{ $tenant->is_active ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-emerald-200 text-emerald-600 hover:bg-emerald-50' }} transition">
                                                    {{ $tenant->is_active ? 'Disable' : 'Enable' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-gray-400">No organizations found. Create one on the left.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

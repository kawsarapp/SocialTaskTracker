<x-app-layout>
    @php
        $activeTenant = $currentTenant ?? Auth::user()->tenant;
        $taxonomyCount = $taskTypes->count() + $teams->count() + $facebookPages->count() + $youtubeChannels->count() + $areas->count() + $shifts->count();
        $formConfig = $activeTenant?->task_form_config ?? [];
        $canViewAuditLogs = Auth::user()->hasPermission('view_activity_logs');
    @endphp

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-bold text-xl text-gray-900 leading-tight flex items-center gap-2">
                    <span>⚙️</span>
                    <span>{{ __('Workspace Settings & Configuration Hub') }}</span>
                    @if($activeTenant)
                        <span class="px-2.5 py-0.5 text-xs font-extrabold rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                            {{ $activeTenant->name }}
                        </span>
                    @endif
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Manage staff roles, granular permissions, publishing channels, custom task forms, and API integrations.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if(Auth::user()->isSuperAdmin() && isset($allTenants) && $allTenants->count() > 0)
                    <form action="{{ route('settings.switch-tenant') }}" method="POST" class="inline-flex items-center gap-1.5 bg-indigo-50/80 border border-indigo-200 px-2.5 py-1 rounded-lg">
                        @csrf
                        <span class="text-[11px] font-bold text-indigo-700 whitespace-nowrap">🏢 Workspace:</span>
                        <select name="tenant_id" onchange="this.form.submit()" class="text-xs font-bold text-indigo-900 bg-white border-indigo-200 rounded-md py-1 pl-2 pr-6 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                            @foreach($allTenants as $t)
                                <option value="{{ $t->id }}" {{ ($activeTenant && $activeTenant->id === $t->id) ? 'selected' : '' }}>
                                    {{ $t->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
                <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-indigo-600 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-xs transition">
                    <span>&larr; Back to Tasks</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8"
         x-data="{
            activeTab: localStorage.getItem('stt_settings_tab') || 'roles',
            staffSearch: '',
            setTab(tab) {
                this.activeTab = tab;
                localStorage.setItem('stt_settings_tab', tab);
            }
         }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Success Message -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center justify-between shadow-sm">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 me-2.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="text-sm font-semibold">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            <!-- Flash Error Message -->
            @if(session('error'))
                <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-center justify-between shadow-sm">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 me-2.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="text-sm font-semibold">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- Validation Errors Alert Banner -->
            @if($errors->any())
                <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl shadow-sm">
                    <div class="flex items-center gap-2 font-bold text-xs uppercase tracking-wider text-rose-700 mb-1.5">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span>Please fix the following errors:</span>
                    </div>
                    <ul class="list-disc list-inside text-xs space-y-1 font-medium">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Top Quick Summary Strip -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                <div @click="setTab('roles')" class="bg-white rounded-xl p-4 border border-gray-100 shadow-xs hover:border-indigo-200 transition cursor-pointer flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400 block">Team & Roles</span>
                        <span class="text-lg font-extrabold text-gray-900">{{ $users->count() }} <span class="text-xs font-semibold text-gray-500">Staff</span></span>
                        <span class="text-[11px] text-indigo-600 font-semibold block">{{ $designations->count() }} Designations</span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">👥</div>
                </div>

                <div @click="setTab('taxonomy')" class="bg-white rounded-xl p-4 border border-gray-100 shadow-xs hover:border-indigo-200 transition cursor-pointer flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400 block">Channels & Tags</span>
                        <span class="text-lg font-extrabold text-gray-900">{{ $taxonomyCount }} <span class="text-xs font-semibold text-gray-500">Items</span></span>
                        <span class="text-[11px] text-emerald-600 font-semibold block">{{ $facebookPages->count() }} FB • {{ $youtubeChannels->count() }} YT</span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">🗂️</div>
                </div>

                <div @click="setTab('form_builder')" class="bg-white rounded-xl p-4 border border-gray-100 shadow-xs hover:border-indigo-200 transition cursor-pointer flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400 block">Task Form Builder</span>
                        <span class="text-lg font-extrabold text-gray-900">{{ count($formConfig) + 1 }} <span class="text-xs font-semibold text-gray-500">Fields</span></span>
                        <span class="text-[11px] text-amber-600 font-semibold block">Drag & Drop Layout</span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-lg">🛠️</div>
                </div>

                <div @click="setTab('integrations')" class="bg-white rounded-xl p-4 border border-gray-100 shadow-xs hover:border-indigo-200 transition cursor-pointer flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400 block">Integrations</span>
                        <span class="text-lg font-extrabold text-gray-900">
                            {{ ($activeTenant?->youtube_api_key || $activeTenant?->facebook_page_access_token || $activeTenant?->slack_webhook_url || $activeTenant?->telegram_bot_token) ? 'Active' : 'Setup' }}
                        </span>
                        <span class="text-[11px] text-purple-600 font-semibold block">Slack • Telegram • API</span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-lg">🔌</div>
                </div>
            </div>

            <!-- Interactive Category Tab Bar -->
            <div class="bg-white rounded-xl border border-gray-200/80 p-2 shadow-xs sticky top-2 z-30">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <!-- 1. Staff & Roles -->
                        <button type="button"
                                @click="setTab('roles')"
                                :class="activeTab === 'roles' ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-600 hover:bg-slate-100 hover:text-gray-900'"
                                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition cursor-pointer">
                            <span>👥</span>
                            <span>Staff & Roles</span>
                            <span :class="activeTab === 'roles' ? 'bg-indigo-500 text-white' : 'bg-gray-100 text-gray-600'"
                                  class="px-1.5 py-0.5 rounded text-[10px] font-extrabold">
                                {{ $users->count() }} • {{ $designations->count() }}
                            </span>
                        </button>

                        <!-- 2. Channels & Taxonomy -->
                        <button type="button"
                                @click="setTab('taxonomy')"
                                :class="activeTab === 'taxonomy' ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-600 hover:bg-slate-100 hover:text-gray-900'"
                                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition cursor-pointer">
                            <span>🗂️</span>
                            <span>Channels & Taxonomy</span>
                            <span :class="activeTab === 'taxonomy' ? 'bg-indigo-500 text-white' : 'bg-gray-100 text-gray-600'"
                                  class="px-1.5 py-0.5 rounded text-[10px] font-extrabold">
                                {{ $taxonomyCount }}
                            </span>
                        </button>

                        <!-- 3. Form Builder -->
                        <button type="button"
                                @click="setTab('form_builder')"
                                :class="activeTab === 'form_builder' ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-600 hover:bg-slate-100 hover:text-gray-900'"
                                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition cursor-pointer">
                            <span>🛠️</span>
                            <span>Form Builder</span>
                            <span :class="activeTab === 'form_builder' ? 'bg-indigo-500 text-white' : 'bg-gray-100 text-gray-600'"
                                  class="px-1.5 py-0.5 rounded text-[10px] font-extrabold">
                                Dynamic Fields
                            </span>
                        </button>

                        <!-- 4. Attendance & Leave -->
                        <button type="button"
                                @click="setTab('attendance')"
                                :class="activeTab === 'attendance' ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-600 hover:bg-slate-100 hover:text-gray-900'"
                                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition cursor-pointer">
                            <span>📅</span>
                            <span>Attendance & Leave</span>
                        </button>

                        <!-- 5. API & Webhooks -->
                        <button type="button"
                                @click="setTab('integrations')"
                                :class="activeTab === 'integrations' ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-600 hover:bg-slate-100 hover:text-gray-900'"
                                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition cursor-pointer">
                            <span>🔌</span>
                            <span>API & Webhooks</span>
                        </button>

                        <!-- 6. Audit Logs -->
                        @if($canViewAuditLogs)
                            <button type="button"
                                    @click="setTab('audit_logs')"
                                    :class="activeTab === 'audit_logs' ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-600 hover:bg-slate-100 hover:text-gray-900'"
                                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition cursor-pointer">
                                <span>🛡️</span>
                                <span>Audit Logs</span>
                                <span :class="activeTab === 'audit_logs' ? 'bg-indigo-500 text-white' : 'bg-gray-100 text-gray-600'"
                                      class="px-1.5 py-0.5 rounded text-[10px] font-extrabold">
                                    {{ count($activityLogs) }}
                                </span>
                            </button>
                        @endif
                    </div>

                    <!-- Right side: View All Toggle -->
                    <button type="button"
                            @click="setTab(activeTab === 'all' ? 'roles' : 'all')"
                            :class="activeTab === 'all' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold transition cursor-pointer shrink-0"
                            title="Toggle showing all sections on a single page">
                        <span>⚡</span>
                        <span x-text="activeTab === 'all' ? 'Showing All Sections' : 'Show All'"></span>
                    </button>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 1: STAFF & ROLES (Designations + Permissions + Staff Creation/Roster) -->
            <!-- ========================================================================= -->
            <div x-show="activeTab === 'roles' || activeTab === 'all'" class="space-y-6">

                <!-- Section 1A: Staff Designations / Roles & 12-Permission Matrix -->
                <div class="grid grid-cols-1 gap-6" x-data="designationPerms()">
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 p-6">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6 pb-5 border-b border-gray-100">
                            <div>
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 mb-1.5">
                                    Role-Based Access Control (RBAC)
                                </div>
                                <h3 class="font-bold text-gray-900 text-base">Staff Designations & Granular Permissions</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Configure positions (e.g. Reporter, Video Editor, SEO Specialist), toggle dashboard task columns, and assign 12 granular SaaS permissions.</p>
                            </div>

                            <!-- Add Designation Inline Form -->
                            <form action="{{ route('settings.designations.store') }}" method="POST" class="flex flex-wrap sm:flex-nowrap items-end gap-3 bg-slate-50 p-3.5 rounded-xl border border-slate-200/70">
                                @csrf
                                <div class="w-full sm:w-52">
                                    <label for="desg_name" class="block text-[11px] font-bold text-gray-600 mb-1">New Designation Name</label>
                                    <input type="text" name="name" id="desg_name" required placeholder="e.g. SEO Specialist" class="w-full border-gray-200 rounded-lg text-xs focus:ring focus:ring-indigo-100 bg-white py-2">
                                </div>
                                <div>
                                    <label for="desg_color" class="block text-[11px] font-bold text-gray-600 mb-1">Badge Color</label>
                                    <div class="flex gap-2 items-center">
                                        <input type="color" name="color" id="desg_color" value="#e2e8f0" class="w-9 h-9 p-0.5 border border-gray-200 rounded-lg cursor-pointer bg-white">
                                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-indigo-700 transition shadow-xs whitespace-nowrap cursor-pointer">
                                            + Add Role
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-gray-100 text-gray-400 uppercase font-bold tracking-wider bg-slate-50/60">
                                        <th class="py-3 px-3 font-semibold rounded-tl-lg">Designation</th>
                                        <th class="py-3 px-3 font-semibold text-center">Dashboard Task Column</th>
                                        <th class="py-3 px-3 font-semibold">Assigned Permissions</th>
                                        <th class="py-3 px-3 font-semibold text-right rounded-tr-lg">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($designations as $desg)
                                        <tr class="hover:bg-slate-50/70 transition">
                                            <td class="py-3.5 px-3 whitespace-nowrap">
                                                <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold border border-gray-200 shadow-2xs" style="background-color: {{ $desg->color ?? '#e2e8f0' }}; color: #1e293b;">
                                                    {{ $desg->name }}
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                                <form action="{{ route('settings.designations.toggle-column', $desg->id) }}" method="POST" class="inline">
                                                    @csrf @method('PUT')
                                                    <button type="submit" class="px-2.5 py-1 rounded-md text-[11px] font-bold shadow-2xs transition cursor-pointer {{ $desg->is_task_column ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200 border border-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 border border-slate-200' }}" title="Toggle visibility as a dashboard task column">
                                                        {{ $desg->is_task_column ? '✓ Column: Active' : 'Column: Hidden' }}
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="py-3.5 px-3 text-gray-500">
                                                @php
                                                    $perms = $desg->permissions ?? [];
                                                @endphp
                                                @if(empty($perms))
                                                    <span class="text-gray-400 italic text-[11px]">No custom permissions assigned (View only)</span>
                                                @else
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($perms as $p)
                                                            <span class="px-2 py-0.5 rounded bg-indigo-50/70 border border-indigo-100 text-indigo-700 text-[10px] font-semibold">
                                                                {{ str_replace('_', ' ', $p) }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="py-3.5 px-3 text-right whitespace-nowrap">
                                                <div class="inline-flex items-center gap-2">
                                                    <!-- Edit Permissions Button -->
                                                    <button type="button" 
                                                        @click="openPermissionsModalById({{ $desg->id }})"
                                                        class="inline-flex items-center gap-1.5 text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200/80 font-bold px-2.5 py-1.5 rounded-lg text-[11px] transition cursor-pointer" 
                                                        title="Edit Permissions">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                                        <span>Permissions</span>
                                                    </button>

                                                    <!-- Delete Form -->
                                                    <form action="{{ route('settings.designations.destroy', $desg->id) }}" method="POST" class="inline">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" 
                                                            class="text-rose-500 hover:text-rose-700 font-bold p-1.5 rounded-lg hover:bg-rose-50 transition cursor-pointer" 
                                                            title="Delete Designation"
                                                            onclick="return confirm('Are you sure you want to delete this designation?')">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-6 text-center text-gray-400 italic">No designations configured yet. Add one above to get started.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Permissions Modal Overlay -->
                    <div x-show="permissionsModalOpen" 
                         style="display: none;"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         @click.self="closePermissionsModal()" 
                         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4" 
                         x-cloak>
                        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full border border-gray-100 p-6 relative">
                            <button type="button" @click.prevent.stop="closePermissionsModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 font-bold text-lg p-1 cursor-pointer">&times;</button>
                            
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4 pr-6">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-lg">Edit Designation Permissions</h3>
                                    <p class="text-xs text-gray-400 mt-0.5">Configure granular SaaS access for <span class="font-bold text-indigo-600" x-text="selectedDesg.name"></span>.</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button" @click="selectAllPerms()" class="text-[11px] font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-2.5 py-1 rounded-lg transition cursor-pointer">
                                        Select All
                                    </button>
                                    <button type="button" @click="clearAllPerms()" class="text-[11px] font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 border border-gray-200 px-2.5 py-1 rounded-lg transition cursor-pointer">
                                        Clear All
                                    </button>
                                </div>
                            </div>
                            
                            <form id="designation-permissions-form" @submit.prevent="savePermissions()" class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-[60vh] overflow-y-auto pr-1">
                                    <!-- 1. View Tasks / Dashboard -->
                                    <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition">
                                        <input type="checkbox" name="permissions[]" value="view_tasks" :checked="hasPerm('view_tasks')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1">
                                        <div>
                                            <span class="block text-xs font-bold text-gray-800">View Tasks / Dashboard</span>
                                            <span class="block text-[11px] text-gray-400 leading-tight mt-0.5">Access the main task table and KPI summary strip.</span>
                                        </div>
                                    </label>

                                    <!-- 2. Log/Create Tasks -->
                                    <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition">
                                        <input type="checkbox" name="permissions[]" value="create_tasks" :checked="hasPerm('create_tasks')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1">
                                        <div>
                                            <span class="block text-xs font-bold text-gray-800">Log/Create Tasks</span>
                                            <span class="block text-[11px] text-gray-400 leading-tight mt-0.5">Create and log new tasks and assignments.</span>
                                        </div>
                                    </label>

                                    <!-- 3. Edit All Tasks -->
                                    <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition">
                                        <input type="checkbox" name="permissions[]" value="edit_all_tasks" :checked="hasPerm('edit_all_tasks')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1">
                                        <div>
                                            <span class="block text-xs font-bold text-gray-800">Edit All Tasks</span>
                                            <span class="block text-[11px] text-gray-400 leading-tight mt-0.5">Edit any team member's task (not just owned/assigned).</span>
                                        </div>
                                    </label>

                                    <!-- 4. Delete Tasks -->
                                    <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition">
                                        <input type="checkbox" name="permissions[]" value="delete_tasks" :checked="hasPerm('delete_tasks')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1">
                                        <div>
                                            <span class="block text-xs font-bold text-gray-800">Delete Tasks</span>
                                            <span class="block text-[11px] text-gray-400 leading-tight mt-0.5">Permanently delete task entries from the workspace.</span>
                                        </div>
                                    </label>

                                    <!-- 5. Quick Update Social Links -->
                                    <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition">
                                        <input type="checkbox" name="permissions[]" value="quick_update_links" :checked="hasPerm('quick_update_links')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1">
                                        <div>
                                            <span class="block text-xs font-bold text-gray-800">Quick Update Social Links</span>
                                            <span class="block text-[11px] text-gray-400 leading-tight mt-0.5">Inline-update Facebook &amp; YouTube links directly from the table.</span>
                                        </div>
                                    </label>

                                    <!-- 6. Bulk Task Operations -->
                                    <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition">
                                        <input type="checkbox" name="permissions[]" value="bulk_actions" :checked="hasPerm('bulk_actions')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1">
                                        <div>
                                            <span class="block text-xs font-bold text-gray-800">Bulk Task Operations</span>
                                            <span class="block text-[11px] text-gray-400 leading-tight mt-0.5">Select multiple tasks for bulk reassignment or deletion.</span>
                                        </div>
                                    </label>

                                    <!-- 7. Export Tasks CSV -->
                                    <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition">
                                        <input type="checkbox" name="permissions[]" value="export_tasks" :checked="hasPerm('export_tasks')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1">
                                        <div>
                                            <span class="block text-xs font-bold text-gray-800">Export Tasks CSV</span>
                                            <span class="block text-[11px] text-gray-400 leading-tight mt-0.5">Download filtered task reports as CSV spreadsheets.</span>
                                        </div>
                                    </label>

                                    <!-- 8. View Content Calendar -->
                                    <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition">
                                        <input type="checkbox" name="permissions[]" value="view_calendar" :checked="hasPerm('view_calendar')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1">
                                        <div>
                                            <span class="block text-xs font-bold text-gray-800">View Content Calendar</span>
                                            <span class="block text-[11px] text-gray-400 leading-tight mt-0.5">Access the visual monthly/weekly content calendar schedule.</span>
                                        </div>
                                    </label>

                                    <!-- 9. View Analytics & Charts -->
                                    <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition">
                                        <input type="checkbox" name="permissions[]" value="view_analytics" :checked="hasPerm('view_analytics')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1">
                                        <div>
                                            <span class="block text-xs font-bold text-gray-800">View Analytics &amp; Charts</span>
                                            <span class="block text-[11px] text-gray-400 leading-tight mt-0.5">Access productivity reports, charts, and social performance metrics.</span>
                                        </div>
                                    </label>

                                    <!-- 10. Sync Social Metrics -->
                                    <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition">
                                        <input type="checkbox" name="permissions[]" value="sync_social_metrics" :checked="hasPerm('sync_social_metrics')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1">
                                        <div>
                                            <span class="block text-xs font-bold text-gray-800">Sync Social Metrics</span>
                                            <span class="block text-[11px] text-gray-400 leading-tight mt-0.5">Trigger live Facebook/YouTube API metric synchronization.</span>
                                        </div>
                                    </label>

                                    <!-- 11. Manage Workspace Settings -->
                                    <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition">
                                        <input type="checkbox" name="permissions[]" value="manage_settings" :checked="hasPerm('manage_settings')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1">
                                        <div>
                                            <span class="block text-xs font-bold text-gray-800">Manage Workspace Settings</span>
                                            <span class="block text-[11px] text-gray-400 leading-tight mt-0.5">Configure teams, shifts, task types, columns, and staff roles.</span>
                                        </div>
                                    </label>

                                    <!-- 12. View Audit & Activity Logs -->
                                    <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition">
                                        <input type="checkbox" name="permissions[]" value="view_activity_logs" :checked="hasPerm('view_activity_logs')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1">
                                        <div>
                                            <span class="block text-xs font-bold text-gray-800">View Audit &amp; Activity Logs</span>
                                            <span class="block text-[11px] text-gray-400 leading-tight mt-0.5">View full workspace revision history and live activity feed.</span>
                                        </div>
                                    </label>
                                </div>
                                
                                <div x-show="saveError" class="text-xs text-red-600 font-semibold" x-text="saveError"></div>
                                <div class="flex gap-3 justify-end pt-4 border-t border-gray-100">
                                    <button type="button" @click="closePermissionsModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-4 rounded-lg text-xs transition cursor-pointer">Cancel</button>
                                    <button type="submit" :disabled="saving" class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-bold py-2 px-4 rounded-lg text-xs transition shadow-sm cursor-pointer" x-text="saving ? 'Saving...' : 'Save Changes'"></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Section 1B: Add New Staff Member & Searchable Team Roster -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    <!-- Add New Staff Card (5 cols) -->
                    <div class="lg:col-span-5 bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 p-6">
                        <h3 class="text-base font-bold text-gray-800 mb-1 flex items-center">
                            <svg class="w-5 h-5 text-indigo-500 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Add New Staff Member
                        </h3>
                        <p class="text-xs text-gray-400 mb-5">Provision a new account for your workspace team.</p>

                        <form action="{{ route('settings.users.store') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label for="user_name" class="block text-xs font-bold text-gray-600 mb-1">Full Name</label>
                                <input type="text" name="name" id="user_name" required placeholder="e.g. Tanvir Ahmed" 
                                    class="w-full border-gray-200 rounded-lg text-xs focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                            </div>

                            <div>
                                <label for="user_email" class="block text-xs font-bold text-gray-600 mb-1">Email Address</label>
                                <input type="email" name="email" id="user_email" required placeholder="staff@company.com" 
                                    class="w-full border-gray-200 rounded-lg text-xs focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="user_password" class="block text-xs font-bold text-gray-600 mb-1">Default Password</label>
                                    <input type="text" name="password" id="user_password" required value="password123" 
                                        class="w-full border-gray-200 rounded-lg text-xs focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                </div>

                                <div>
                                    <label for="user_role" class="block text-xs font-bold text-gray-600 mb-1">System Access Level</label>
                                    <select name="role" id="user_role" required 
                                        class="w-full border-gray-200 rounded-lg text-xs focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                        <option value="staff">Staff (Standard Entry)</option>
                                        <option value="team_leader">Team Leader</option>
                                        <option value="admin">Tenant Admin</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label for="user_designation" class="block text-xs font-bold text-gray-600 mb-1">Designation / Role (Optional)</label>
                                <select name="designation_id" id="user_designation" 
                                    class="w-full border-gray-200 rounded-lg text-xs focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                    <option value="">Select Designation / Role</option>
                                    @foreach($designations as $desg)
                                        <option value="{{ $desg->id }}">{{ $desg->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded-lg shadow-xs text-xs transition duration-150 cursor-pointer">
                                + Create User Account
                            </button>
                        </form>
                    </div>

                    <!-- Searchable Team Roster Card (7 cols) -->
                    <div class="lg:col-span-7 bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-4 border-b border-gray-100">
                            <div>
                                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                                    <span>Workspace Team Roster</span>
                                    <span class="px-2 py-0.5 text-xs font-extrabold rounded-full bg-indigo-50 text-indigo-600">{{ $users->count() }}</span>
                                </h3>
                                <p class="text-xs text-gray-400">Active members registered under this workspace tenant.</p>
                            </div>
                            <div class="relative w-full sm:w-60">
                                <input type="text" x-model="staffSearch" placeholder="Filter staff by name, email, role..." 
                                       class="w-full rounded-lg border-gray-200 text-xs focus:border-indigo-500 focus:ring-indigo-500 pl-8 pr-3 py-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[380px] overflow-y-auto pr-1">
                            @foreach($users as $u)
                                @php
                                    $staffSearchable = strtolower($u->name . ' ' . $u->email . ' ' . $u->role . ' ' . ($u->designation->name ?? ''));
                                @endphp
                                <div x-show="!staffSearch || {{ json_encode($staffSearchable) }}.includes(staffSearch.toLowerCase())"
                                     class="flex justify-between items-center bg-slate-50/80 hover:bg-slate-100/70 p-3 rounded-xl border border-gray-100 transition">
                                    <div class="min-w-0 pr-2">
                                        <div class="text-xs font-bold text-gray-800 truncate">{{ $u->name }}</div>
                                        <div class="text-[11px] text-gray-500 truncate">{{ $u->email }}</div>
                                    </div>
                                    <div class="flex flex-col gap-1 items-end shrink-0">
                                        <span class="inline-flex px-2 py-0.5 text-[10px] font-bold rounded-full {{ $u->role === 'admin' ? 'bg-purple-100 text-purple-800' : ($u->role === 'team_leader' ? 'bg-blue-100 text-blue-800' : 'bg-gray-200/80 text-gray-700') }}">
                                            {{ ucfirst(str_replace('_', ' ', $u->role)) }}
                                        </span>
                                        @if($u->designation)
                                            <span class="inline-flex px-2 py-0.5 text-[10px] font-bold rounded border border-gray-200/80" style="background-color: {{ $u->designation->color }}; color: #1e293b;">
                                                {{ $u->designation->name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 2: CHANNELS & TAXONOMY (6 Quick-Config Cards in 3-Col Grid)           -->
            <!-- ========================================================================= -->
            <div x-show="activeTab === 'taxonomy' || activeTab === 'all'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <!-- 1. Task Types -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-sm">Task Types</h3>
                                    <p class="text-[11px] text-gray-400">Format badges (e.g. LIVE, PKG, REEL)</p>
                                </div>
                                <span class="px-2 py-0.5 text-xs font-extrabold rounded-full bg-indigo-50 text-indigo-600">{{ $taskTypes->count() }}</span>
                            </div>
                            <form action="{{ route('settings.task-types.store') }}" method="POST" class="flex gap-2 mb-4">
                                @csrf
                                <input type="text" name="name" required placeholder="Type Name" class="flex-1 border-gray-200 rounded-lg text-xs focus:ring focus:ring-indigo-100">
                                <input type="color" name="color" value="#fee2e2" class="w-9 h-8 p-0.5 border border-gray-200 rounded-lg cursor-pointer bg-white">
                                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-indigo-700 transition cursor-pointer">Add</button>
                            </form>
                            <div class="flex flex-wrap gap-2 max-h-56 overflow-y-auto pr-1">
                                @forelse($taskTypes as $t)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border border-gray-200 shadow-2xs" style="background-color: {{ $t->color ?? '#e2e8f0' }}; color: #1e293b;">
                                        {{ $t->name }}
                                        <form action="{{ route('settings.task-types.destroy', $t->id) }}" method="POST" class="inline ms-1.5">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-500 hover:text-red-600 font-bold cursor-pointer">&times;</button>
                                        </form>
                                    </span>
                                @empty
                                    <p class="text-xs text-gray-400 italic">No task types configured.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- 2. Teams -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-sm">Production Teams</h3>
                                    <p class="text-[11px] text-gray-400">Units & editorial squads</p>
                                </div>
                                <span class="px-2 py-0.5 text-xs font-extrabold rounded-full bg-indigo-50 text-indigo-600">{{ $teams->count() }}</span>
                            </div>
                            <form action="{{ route('settings.teams.store') }}" method="POST" class="flex gap-2 mb-4">
                                @csrf
                                <input type="text" name="name" required placeholder="Team Name" class="flex-1 border-gray-200 rounded-lg text-xs focus:ring focus:ring-indigo-100">
                                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-indigo-700 transition cursor-pointer">Add</button>
                            </form>
                            <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                                @forelse($teams as $team)
                                    <div class="flex justify-between items-center bg-slate-50 px-3 py-2 rounded-lg border border-gray-100">
                                        <span class="text-xs font-semibold text-gray-700">{{ $team->name }}</span>
                                        <form action="{{ route('settings.teams.destroy', $team->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-500 text-sm font-bold cursor-pointer">&times;</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400 italic">No teams configured.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- 3. Shifts -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-sm">Work Shifts</h3>
                                    <p class="text-[11px] text-gray-400">Duty schedules (e.g. Morning, Night)</p>
                                </div>
                                <span class="px-2 py-0.5 text-xs font-extrabold rounded-full bg-indigo-50 text-indigo-600">{{ $shifts->count() }}</span>
                            </div>
                            <form action="{{ route('settings.shifts.store') }}" method="POST" class="flex gap-2 mb-4">
                                @csrf
                                <input type="text" name="name" required placeholder="Shift Name (e.g. Morning)" class="flex-1 border-gray-200 rounded-lg text-xs focus:ring focus:ring-indigo-100">
                                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-indigo-700 transition cursor-pointer">Add</button>
                            </form>
                            <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                                @forelse($shifts as $shift)
                                    <div class="flex justify-between items-center bg-slate-50 px-3 py-2 rounded-lg border border-gray-100">
                                        <span class="text-xs font-semibold text-gray-700">{{ $shift->name }}</span>
                                        <form action="{{ route('settings.shifts.destroy', $shift->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-500 text-sm font-bold cursor-pointer">&times;</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400 italic">No shifts configured.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- 4. Facebook Pages -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-sm">Facebook Pages</h3>
                                    <p class="text-[11px] text-gray-400">Target pages for social publishing</p>
                                </div>
                                <span class="px-2 py-0.5 text-xs font-extrabold rounded-full bg-blue-50 text-blue-600">{{ $facebookPages->count() }}</span>
                            </div>
                            <form action="{{ route('settings.fb-pages.store') }}" method="POST" class="flex gap-2 mb-4">
                                @csrf
                                <input type="text" name="name" required placeholder="Page URL or Name" class="flex-1 border-gray-200 rounded-lg text-xs focus:ring focus:ring-indigo-100">
                                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-indigo-700 transition cursor-pointer">Add</button>
                            </form>
                            <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                                @forelse($facebookPages as $page)
                                    <div class="flex justify-between items-center bg-slate-50 px-3 py-2 rounded-lg border border-gray-100">
                                        <span class="text-xs font-semibold text-gray-700 truncate pr-2">{{ $page->name }}</span>
                                        <form action="{{ route('settings.fb-pages.destroy', $page->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-500 text-sm font-bold cursor-pointer">&times;</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400 italic">No Facebook pages configured.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- 5. YouTube Channels -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-sm">YouTube Channels</h3>
                                    <p class="text-[11px] text-gray-400">Target channels for video uploads</p>
                                </div>
                                <span class="px-2 py-0.5 text-xs font-extrabold rounded-full bg-rose-50 text-rose-600">{{ $youtubeChannels->count() }}</span>
                            </div>
                            <form action="{{ route('settings.yt-channels.store') }}" method="POST" class="flex gap-2 mb-4">
                                @csrf
                                <input type="text" name="name" required placeholder="Channel Name" class="flex-1 border-gray-200 rounded-lg text-xs focus:ring focus:ring-indigo-100">
                                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-indigo-700 transition cursor-pointer">Add</button>
                            </form>
                            <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                                @forelse($youtubeChannels as $chan)
                                    <div class="flex justify-between items-center bg-slate-50 px-3 py-2 rounded-lg border border-gray-100">
                                        <span class="text-xs font-semibold text-gray-700 truncate pr-2">{{ $chan->name }}</span>
                                        <form action="{{ route('settings.yt-channels.destroy', $chan->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-500 text-sm font-bold cursor-pointer">&times;</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400 italic">No YouTube channels configured.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- 6. Areas / News Sources -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-sm">Areas / News Sources</h3>
                                    <p class="text-[11px] text-gray-400">Geographic regions or beats</p>
                                </div>
                                <span class="px-2 py-0.5 text-xs font-extrabold rounded-full bg-emerald-50 text-emerald-600">{{ $areas->count() }}</span>
                            </div>
                            <form action="{{ route('settings.areas.store') }}" method="POST" class="flex gap-2 mb-4">
                                @csrf
                                <input type="text" name="name" required placeholder="Area/Source Name" class="flex-1 border-gray-200 rounded-lg text-xs focus:ring focus:ring-indigo-100">
                                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-indigo-700 transition cursor-pointer">Add</button>
                            </form>
                            <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                                @forelse($areas as $area)
                                    <div class="flex justify-between items-center bg-slate-50 px-3 py-2 rounded-lg border border-gray-100">
                                        <span class="text-xs font-semibold text-gray-700 truncate pr-2">{{ $area->name }}</span>
                                        <form action="{{ route('settings.areas.destroy', $area->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-500 text-sm font-bold cursor-pointer">&times;</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400 italic">No areas configured.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 3: FORM BUILDER (Dynamic Task Form Configuration)                     -->
            <!-- ========================================================================= -->
            <div x-show="activeTab === 'form_builder' || activeTab === 'all'" class="space-y-6">
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
                            <h3 class="font-bold text-gray-800 text-base mb-1 flex items-center">
                                <svg class="w-5 h-5 text-indigo-500 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                                    <button type="button" @click="deleteField(field.key)" class="text-red-500 hover:text-red-700 transition cursor-pointer" title="Delete Custom Field">
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
                                <svg class="w-4 h-4 text-indigo-500 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold py-2 px-4 rounded-lg shadow-xs transition h-9 cursor-pointer">
                                    + Add Field
                                </button>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-lg text-xs shadow-sm transition cursor-pointer">
                                Save Configured Layout
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 4: ATTENDANCE & LEAVE MANAGEMENT                                      -->
            <!-- ========================================================================= -->
            <div x-show="activeTab === 'attendance' || activeTab === 'all'" class="space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 text-base mb-1 flex items-center">
                        <svg class="w-5 h-5 text-indigo-500 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Attendance & Leave Management
                    </h3>
                    <p class="text-xs text-gray-400 mb-6">Assign planned or sick leave for team members and monitor today's live check-in status.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Leave Request Form -->
                        <div>
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Mark Staff on Leave</h4>
                            <form action="{{ route('settings.attendance.leave') }}" method="POST" class="space-y-4 bg-slate-50 p-5 rounded-xl border border-gray-100">
                                @csrf
                                <div>
                                    <label for="leave_user_id" class="block text-xs font-bold text-gray-600 mb-1">Select Staff</label>
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
                                    <label for="leave_date" class="block text-xs font-bold text-gray-600 mb-1">Date</label>
                                    <input type="date" name="date" id="leave_date" required value="{{ date('Y-m-d') }}" class="w-full border-gray-200 rounded-lg text-xs bg-white focus:ring focus:ring-indigo-100">
                                </div>
                                <div>
                                    <label for="leave_remarks" class="block text-xs font-bold text-gray-600 mb-1">Reason / Remarks</label>
                                    <input type="text" name="remarks" id="leave_remarks" required placeholder="e.g. Sick Leave, Annual Leave" class="w-full border-gray-200 rounded-lg text-xs bg-white focus:ring focus:ring-indigo-100">
                                </div>
                                <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 rounded-lg text-xs shadow-xs transition cursor-pointer">
                                    Mark as On Leave
                                </button>
                            </form>
                        </div>

                        <!-- Attendance Roster Today -->
                        <div>
                            @php
                                $todayAttendances = \App\Models\Attendance::where('date', now()->format('Y-m-d'))->get()->keyBy('user_id');
                            @endphp
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Today's Attendance Roster ({{ now()->format('d M Y') }})</h4>
                            <div class="space-y-2 max-h-[260px] overflow-y-auto pr-1">
                                @forelse($users as $staff)
                                    @if($staff->role !== 'super_admin')
                                        @php
                                            $att = $todayAttendances->get($staff->id);
                                        @endphp
                                        <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-gray-100 text-xs">
                                            <div>
                                                <span class="font-bold text-gray-800">{{ $staff->name }}</span>
                                                <span class="text-gray-400"> ({{ ucfirst($staff->role) }})</span>
                                            </div>
                                            <div>
                                                @if($att)
                                                    @if($att->status === 'leave')
                                                        <span class="inline-flex px-2.5 py-0.5 font-bold rounded-full bg-amber-50 border border-amber-200 text-amber-800" title="{{ $att->remarks }}">
                                                            🌴 On Leave
                                                        </span>
                                                    @else
                                                        <span class="inline-flex px-2.5 py-0.5 font-bold rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800" title="Check-in: {{ $att->check_in ? $att->check_in->format('h:i A') : '-' }}">
                                                            ✓ Present
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="inline-flex px-2.5 py-0.5 font-bold rounded-full bg-rose-50 border border-rose-200 text-rose-800">
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
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 5: API & WEBHOOKS (Messaging Webhooks + Social API Keys & Sync)       -->
            <!-- ========================================================================= -->
            <div x-show="activeTab === 'integrations' || activeTab === 'all'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                    
                    <!-- Messaging Webhooks Card -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 p-6">
                        <h3 class="text-base font-bold text-gray-800 mb-1 flex items-center">
                            <svg class="w-5 h-5 text-indigo-500 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            Messaging Webhooks
                        </h3>
                        <p class="text-xs text-gray-400 mb-6">Receive real-time automated chat updates on Slack and Telegram when content goes live.</p>
                        
                        <form action="{{ route('settings.webhooks.update') }}" method="POST" class="space-y-4">
                            @csrf
                            <!-- Slack Webhook -->
                            <div>
                                <label for="slack_webhook_url" class="block text-xs font-bold text-gray-600 mb-1">Slack Webhook URL</label>
                                <input type="url" name="slack_webhook_url" id="slack_webhook_url" 
                                    value="{{ old('slack_webhook_url', $activeTenant?->slack_webhook_url) }}"
                                    placeholder="https://hooks.slack.com/services/..." 
                                    class="w-full border-gray-200 rounded-lg text-xs focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                @error('slack_webhook_url')
                                    <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Telegram Bot Token -->
                            <div>
                                <label for="telegram_bot_token" class="block text-xs font-bold text-gray-600 mb-1">Telegram Bot Token</label>
                                <input type="text" name="telegram_bot_token" id="telegram_bot_token" 
                                    value="{{ old('telegram_bot_token', $activeTenant?->telegram_bot_token) }}"
                                    placeholder="e.g. 123456789:ABCdefGhI..." 
                                    class="w-full border-gray-200 rounded-lg text-xs focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                @error('telegram_bot_token')
                                    <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Telegram Chat ID -->
                            <div>
                                <label for="telegram_chat_id" class="block text-xs font-bold text-gray-600 mb-1">Telegram Chat ID</label>
                                <input type="text" name="telegram_chat_id" id="telegram_chat_id" 
                                    value="{{ old('telegram_chat_id', $activeTenant?->telegram_chat_id) }}"
                                    placeholder="e.g. -100123456789" 
                                    class="w-full border-gray-200 rounded-lg text-xs focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                @error('telegram_chat_id')
                                    <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 rounded-lg text-xs shadow-xs transition cursor-pointer">
                                Save Webhook Settings
                            </button>
                        </form>
                    </div>

                    <!-- Social API Integration Card -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 p-6">
                        <h3 class="text-base font-bold text-gray-800 mb-1 flex items-center">
                            <svg class="w-5 h-5 text-indigo-500 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                            Social API Integration
                        </h3>
                        <p class="text-xs text-gray-400 mb-6">Auto-sync video views, likes, and reach on YouTube and Facebook page clips.</p>
                        
                        <form action="{{ route('settings.api-keys.update') }}" method="POST" class="space-y-4">
                            @csrf
                            <!-- YouTube API Key -->
                            <div>
                                <label for="youtube_api_key" class="block text-xs font-bold text-gray-600 mb-1">YouTube API Key</label>
                                <input type="text" name="youtube_api_key" id="youtube_api_key" 
                                    value="{{ old('youtube_api_key', $activeTenant?->youtube_api_key) }}"
                                    placeholder="AIzaSy..." 
                                    class="w-full border-gray-200 rounded-lg text-xs focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                @error('youtube_api_key')
                                    <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Facebook Page Access Token -->
                            <div>
                                <label for="facebook_page_access_token" class="block text-xs font-bold text-gray-600 mb-1">Facebook Page Access Token</label>
                                <textarea name="facebook_page_access_token" id="facebook_page_access_token" rows="3"
                                    placeholder="EAAG..." 
                                    class="w-full border-gray-200 rounded-lg text-xs focus:border-indigo-500 focus:ring focus:ring-indigo-100">{{ old('facebook_page_access_token', $activeTenant?->facebook_page_access_token) }}</textarea>
                                @error('facebook_page_access_token')
                                    <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Sync Interval Dropdown -->
                            <div>
                                <label for="api_sync_interval" class="block text-xs font-bold text-gray-600 mb-1">Auto Sync Frequency (Quota Guard)</label>
                                <select name="api_sync_interval" id="api_sync_interval" 
                                    class="w-full border-gray-200 rounded-lg text-xs focus:border-indigo-500 focus:ring focus:ring-indigo-100">
                                    @php
                                        $interval = $activeTenant?->api_sync_interval ?? 0;
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
                                <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 rounded-lg text-xs shadow-xs transition cursor-pointer">
                                    Save API Keys
                                </button>
                            </div>
                        </form>

                        <!-- Manual Bulk Sync Form -->
                        @if($activeTenant?->youtube_api_key || $activeTenant?->facebook_page_access_token)
                            <div class="mt-6 pt-6 border-t border-gray-100">
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">On-Demand Bulk Sync</h4>
                                <p class="text-[11px] text-gray-400 mb-4">Trigger an immediate manual update of stats for all tasks logged in the last 7 days.</p>
                                
                                @if($activeTenant?->last_api_sync_at)
                                    <div class="mb-3 text-[11px] text-gray-500 flex items-center bg-gray-50 p-2 rounded border border-gray-100">
                                        <svg class="w-3.5 h-3.5 text-gray-400 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Last Bulk Sync: {{ $activeTenant->last_api_sync_at->format('d M Y h:i A') }}
                                    </div>
                                @endif

                                <form action="{{ route('settings.api-keys.sync') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 rounded-lg text-xs shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18v3z"></path></svg>
                                        Sync All Tasks Now
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>

                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 6: AUDIT LOGS & REVISION HISTORY                                      -->
            <!-- ========================================================================= -->
            @if($canViewAuditLogs)
                <div x-show="activeTab === 'audit_logs' || activeTab === 'all'" class="space-y-6">
                    <div x-data="{ logSearch: '', logCat: 'all' }" class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5">
                            <div>
                                <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Audit Logs &amp; Revision History
                                </h3>
                                <p class="text-xs text-gray-400 mt-0.5">Comprehensive audit trail of tasks, bulk operations, settings changes, and attendance.</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Category Filter Tabs -->
                                <div class="inline-flex bg-slate-100 p-1 rounded-lg border border-gray-200 text-[11px] font-bold">
                                    <button type="button" @click="logCat = 'all'" :class="logCat === 'all' ? 'bg-white text-indigo-600 shadow-xs' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-md transition cursor-pointer">All</button>
                                    <button type="button" @click="logCat = 'tasks'" :class="logCat === 'tasks' ? 'bg-white text-indigo-600 shadow-xs' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-md transition cursor-pointer">Tasks</button>
                                    <button type="button" @click="logCat = 'settings'" :class="logCat === 'settings' ? 'bg-white text-indigo-600 shadow-xs' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-md transition cursor-pointer">Settings</button>
                                    <button type="button" @click="logCat = 'attendance'" :class="logCat === 'attendance' ? 'bg-white text-indigo-600 shadow-xs' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-md transition cursor-pointer">Attendance</button>
                                </div>

                                <!-- Search Input -->
                                <div class="relative">
                                    <input type="text" x-model="logSearch" placeholder="Search user, action, title..." class="rounded-lg border-gray-200 text-xs focus:border-indigo-500 focus:ring-indigo-500 pl-8 pr-3 py-1.5 w-48 sm:w-56">
                                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-3 max-h-[32rem] overflow-y-auto pr-1">
                            @forelse($activityLogs as $log)
                                @php
                                    $act = $log->action;
                                    if (in_array($act, ['created', 'updated', 'deleted', 'link_updated', 'bulk_updated', 'bulk_deleted', 'exported_csv'])) {
                                        $cat = 'tasks';
                                    } elseif (in_array($act, ['attendance_checkin', 'attendance_checkout', 'leave_requested'])) {
                                        $cat = 'attendance';
                                    } else {
                                        $cat = 'settings';
                                    }

                                    $badgeStyle = match($act) {
                                        'created', 'staff_created', 'designation_created' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                                        'updated', 'settings_updated', 'form_config_updated', 'webhooks_updated', 'api_keys_updated', 'column_toggled', 'permissions_updated' => 'bg-amber-100 text-amber-800 border border-amber-200',
                                        'link_updated' => 'bg-blue-100 text-blue-800 border border-blue-200',
                                        'bulk_updated', 'exported_csv' => 'bg-indigo-100 text-indigo-800 border border-indigo-200',
                                        'attendance_checkin', 'attendance_checkout', 'leave_requested' => 'bg-teal-100 text-teal-800 border border-teal-200',
                                        'deleted', 'bulk_deleted', 'designation_deleted' => 'bg-rose-100 text-rose-800 border border-rose-200',
                                        default => 'bg-gray-100 text-gray-700 border border-gray-200',
                                    };

                                    $searchableText = strtolower(($log->user->name ?? 'System') . ' ' . str_replace('_', ' ', $act) . ' ' . ($log->task_title ?? '') . ' #' . ($log->task_id ?? ''));
                                @endphp
                                <div x-show="(logCat === 'all' || logCat === '{{ $cat }}') && (!logSearch || {{ json_encode($searchableText) }}.includes(logSearch.toLowerCase()))"
                                     class="bg-slate-50/80 hover:bg-slate-50 p-4 rounded-xl border border-gray-100 flex flex-col gap-1.5 transition">
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-bold text-gray-800 text-xs">
                                                {{ $log->user->name ?? 'System' }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $badgeStyle }}">
                                                {{ str_replace('_', ' ', $log->action) }}
                                            </span>
                                            <span class="text-xs text-gray-600 font-semibold">
                                                @if($log->task_id)
                                                    <span class="text-indigo-600 font-bold">#{{ $log->task_id }}</span>
                                                @endif
                                                @if($log->task_title)
                                                    &ldquo;{{ $log->task_title }}&rdquo;
                                                @endif
                                            </span>
                                        </div>
                                        <span class="text-[11px] text-gray-400 font-semibold shrink-0">
                                            {{ $log->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                    
                                    @if(is_array($log->changes) && count($log->changes) > 0)
                                        <div class="mt-1 text-xs space-y-1 bg-white p-3 rounded-lg border border-gray-100">
                                            @foreach($log->changes as $field => $diff)
                                                @php
                                                    $oldVal = is_array($diff) && array_key_exists('old', $diff) ? $diff['old'] : null;
                                                    $newVal = is_array($diff) && array_key_exists('new', $diff) ? $diff['new'] : $diff;
                                                    $oldStr = is_array($oldVal) ? implode(', ', $oldVal) : (is_scalar($oldVal) && $oldVal !== '' ? (string)$oldVal : 'empty');
                                                    $newStr = is_array($newVal) ? implode(', ', $newVal) : (is_scalar($newVal) && $newVal !== '' ? (string)$newVal : 'empty');
                                                @endphp
                                                <div class="flex flex-wrap items-center gap-1.5 text-gray-600">
                                                    <span class="font-semibold text-gray-500">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span>
                                                    @if(is_array($diff) && array_key_exists('old', $diff))
                                                        <span class="line-through text-red-500 bg-red-50 px-1.5 py-0.5 rounded break-all">{{ \Illuminate\Support\Str::limit($oldStr, 60) }}</span>
                                                        <span class="text-gray-400">&rarr;</span>
                                                    @endif
                                                    <span class="text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded break-all">{{ \Illuminate\Support\Str::limit($newStr, 80) }}</span>
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
            @endif

        </div>
    </div>

    @php
    $designationsForJs = $designations->mapWithKeys(fn($d) => [
        $d->id => [
            'id'          => $d->id,
            'name'        => $d->name,
            'permissions' => is_array($d->permissions) ? $d->permissions : [],
        ]
    ]);
    @endphp
    <script>
    // All designations data - rendered server-side to avoid HTML attribute quoting issues
    window.__designations = @json($designationsForJs);

    window.designationPerms = function() {
        return {
            permissionsModalOpen: false,
            selectedDesg: { id: null, name: '', permissions: [] },
            saving: false,
            saveError: '',

            openPermissionsModalById(id) {
                const desg = window.__designations[id];
                if (!desg) return;
                this.selectedDesg = {
                    id: desg.id,
                    name: desg.name,
                    permissions: Array.isArray(desg.permissions) ? [...desg.permissions] : []
                };
                this.saveError = '';
                this.permissionsModalOpen = true;
            },

            closePermissionsModal() {
                this.permissionsModalOpen = false;
            },

            hasPerm(permName) {
                return Array.isArray(this.selectedDesg.permissions) &&
                       this.selectedDesg.permissions.includes(permName);
            },

            selectAllPerms() {
                const allPerms = [
                    'view_tasks', 'create_tasks', 'edit_all_tasks', 'delete_tasks',
                    'quick_update_links', 'bulk_actions', 'export_tasks', 'view_calendar',
                    'view_analytics', 'sync_social_metrics', 'manage_settings', 'view_activity_logs'
                ];
                this.selectedDesg.permissions = [...allPerms];
                document.querySelectorAll('#designation-permissions-form input[type=checkbox]').forEach(cb => {
                    cb.checked = true;
                });
            },

            clearAllPerms() {
                this.selectedDesg.permissions = [];
                document.querySelectorAll('#designation-permissions-form input[type=checkbox]').forEach(cb => {
                    cb.checked = false;
                });
            },

            async savePermissions() {
                if (!this.selectedDesg.id) return;
                this.saving = true;
                this.saveError = '';
                const checkboxes = document.querySelectorAll(
                    '#designation-permissions-form input[type=checkbox]:checked'
                );
                const perms = Array.from(checkboxes).map(cb => cb.value);
                const csrfMeta = document.querySelector('meta[name=csrf-token]');
                try {
                    const formData = new FormData();
                    formData.append('_method', 'PUT');
                    formData.append('_token', csrfMeta ? csrfMeta.content : '');
                    perms.forEach(p => formData.append('permissions[]', p));
                    const res = await fetch(
                        '/settings/designations/' + this.selectedDesg.id + '/permissions',
                        { method: 'POST', body: formData }
                    );
                    if (res.ok || res.redirected) {
                        this.permissionsModalOpen = false;
                        window.location.reload();
                    } else {
                        this.saveError = 'Failed to save. Please try again.';
                    }
                } catch(e) {
                    this.saveError = 'Network error. Please try again.';
                }
                this.saving = false;
            }
        };
    };

    document.addEventListener('alpine:init', () => {
        Alpine.data('designationPerms', window.designationPerms);
    });
    if (window.Alpine) {
        window.Alpine.data('designationPerms', window.designationPerms);
    }
    </script>
</x-app-layout>

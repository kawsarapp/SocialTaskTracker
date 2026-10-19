<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\FacebookPage;
use App\Models\TaskType;
use App\Models\Team;
use App\Models\User;
use App\Models\YoutubeChannel;
use App\Models\Shift;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public const AVAILABLE_PERMISSIONS = [
        'view_tasks' => 'View Tasks & Dashboard',
        'create_tasks' => 'Log / Create New Tasks',
        'edit_all_tasks' => 'Edit All Tasks',
        'delete_tasks' => 'Delete Tasks',
        'quick_update_links' => 'Quick-Update FB/YT Links',
        'bulk_actions' => 'Bulk Update / Delete Tasks',
        'export_tasks' => 'Export CSV Reports',
        'view_calendar' => 'View Content Calendar',
        'view_analytics' => 'View Analytics & Charts',
        'sync_social_metrics' => 'Sync Social API Metrics',
        'publish_social_posts' => 'Direct Social Media Publishing (Facebook & YouTube)',
        'manage_settings' => 'Manage Dynamic Settings & Fields',
        'view_activity_logs' => 'View Activity Audit Logs',
    ];

    private function checkAccess()
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isSuperAdmin() && !$user->isTeamLeader() && !$user->hasPermission('manage_settings')) {
            abort(403, 'Unauthorized access.');
        }
    }

    /**
     * Resolve the active tenant for the current user (falls back to session or first tenant for Super Admin).
     */
    private function resolveTenant(): ?\App\Models\Tenant
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        return $user->effectiveTenant();
    }

    /**
     * Allow Super Admin to switch which Tenant workspace is being configured in Settings.
     */
    public function switchTenant(Request $request)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        session(['active_tenant_id' => (int) $request->tenant_id]);
        $tenant = \App\Models\Tenant::find($request->tenant_id);

        return redirect()->route('settings.index')->with('success', "Switched active configuration workspace to {$tenant->name}!");
    }

    public function index()
    {
        $this->checkAccess();

        $currentTenant = $this->resolveTenant();
        $tenantId = $currentTenant?->id;
        $allTenants = Auth::user()->isSuperAdmin() ? \App\Models\Tenant::orderBy('name')->get() : collect();

        // Fetch scoped items for the resolved tenant
        $taskTypes = $tenantId ? TaskType::where('tenant_id', $tenantId)->get() : TaskType::all();
        $facebookPages = $tenantId ? FacebookPage::where('tenant_id', $tenantId)->get() : FacebookPage::all();
        $youtubeChannels = $tenantId ? YoutubeChannel::where('tenant_id', $tenantId)->get() : YoutubeChannel::all();
        $areas = $tenantId ? Area::where('tenant_id', $tenantId)->get() : Area::all();
        $teams = $tenantId ? Team::where('tenant_id', $tenantId)->get() : Team::all();
        $users = $tenantId
            ? User::with('designation')->where('tenant_id', $tenantId)->get()
            : User::with('designation')->get();
        $shifts = $tenantId ? Shift::where('tenant_id', $tenantId)->get() : Shift::all();
        $designations = $tenantId
            ? \App\Models\Designation::where('tenant_id', $tenantId)->get()
            : \App\Models\Designation::all();
        $activityLogs = Auth::user()->hasPermission('view_activity_logs')
            ? ActivityLog::with('user')
                ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                ->orderBy('created_at', 'desc')
                ->take(100)
                ->get()
            : collect();
        $availablePermissions = self::AVAILABLE_PERMISSIONS;

        return view('settings.index', compact(
            'taskTypes', 'facebookPages', 'youtubeChannels', 'areas', 'teams',
            'users', 'shifts', 'designations', 'activityLogs', 'availablePermissions',
            'currentTenant', 'allTenants'
        ));
    }

    // Task Types
    public function storeTaskType(Request $request)
    {
        $this->checkAccess();
        $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'nullable|string|max:7', // Hex color e.g., #ff0000
        ]);

        TaskType::create([
            'tenant_id' => $this->resolveTenant()?->id,
            'name' => $request->name,
            'color' => $request->color ?? '#3490dc',
        ]);

        ActivityLog::record('settings_updated', "Added Task Type: {$request->name}");

        return redirect()->route('settings.index')->with('success', 'Task Type added successfully!');
    }

    public function destroyTaskType(TaskType $taskType)
    {
        $this->checkAccess();
        $name = $taskType->name;
        $taskType->delete();
        ActivityLog::record('settings_updated', "Deleted Task Type: {$name}");
        return redirect()->route('settings.index')->with('success', 'Task Type deleted successfully!');
    }

    // FB Pages
    public function storeFbPage(Request $request)
    {
        $this->checkAccess();
        $request->validate(['name' => 'required|string|max:100']);

        FacebookPage::create([
            'tenant_id' => $this->resolveTenant()?->id,
            'name' => $request->name,
            'is_active' => true,
        ]);
        ActivityLog::record('settings_updated', "Added Facebook Page: {$request->name}");
        return redirect()->route('settings.index')->with('success', 'Facebook Page added successfully!');
    }

    public function destroyFbPage(FacebookPage $facebookPage)
    {
        $this->checkAccess();
        $name = $facebookPage->name;
        $facebookPage->delete();
        ActivityLog::record('settings_updated', "Deleted Facebook Page: {$name}");
        return redirect()->route('settings.index')->with('success', 'Facebook Page deleted successfully!');
    }

    // YT Channels
    public function storeYtChannel(Request $request)
    {
        $this->checkAccess();
        $request->validate(['name' => 'required|string|max:100']);

        YoutubeChannel::create([
            'tenant_id' => $this->resolveTenant()?->id,
            'name' => $request->name,
            'is_active' => true,
        ]);
        ActivityLog::record('settings_updated', "Added YouTube Channel: {$request->name}");
        return redirect()->route('settings.index')->with('success', 'YouTube Channel added successfully!');
    }

    public function destroyYtChannel(YoutubeChannel $youtubeChannel)
    {
        $this->checkAccess();
        $name = $youtubeChannel->name;
        $youtubeChannel->delete();
        ActivityLog::record('settings_updated', "Deleted YouTube Channel: {$name}");
        return redirect()->route('settings.index')->with('success', 'YouTube Channel deleted successfully!');
    }

    // Areas
    public function storeArea(Request $request)
    {
        $this->checkAccess();
        $request->validate(['name' => 'required|string|max:100']);

        Area::create([
            'tenant_id' => $this->resolveTenant()?->id,
            'name' => $request->name,
        ]);
        ActivityLog::record('settings_updated', "Added Area/Source: {$request->name}");
        return redirect()->route('settings.index')->with('success', 'Area/News Source added successfully!');
    }

    public function destroyArea(Area $area)
    {
        $this->checkAccess();
        $name = $area->name;
        $area->delete();
        ActivityLog::record('settings_updated', "Deleted Area/Source: {$name}");
        return redirect()->route('settings.index')->with('success', 'Area deleted successfully!');
    }

    // Teams
    public function storeTeam(Request $request)
    {
        $this->checkAccess();
        $request->validate(['name' => 'required|string|max:100']);

        Team::create([
            'tenant_id' => $this->resolveTenant()?->id,
            'name' => $request->name,
        ]);
        ActivityLog::record('settings_updated', "Added Team: {$request->name}");
        return redirect()->route('settings.index')->with('success', 'Team added successfully!');
    }

    public function destroyTeam(Team $team)
    {
        $this->checkAccess();
        $name = $team->name;
        $team->delete();
        ActivityLog::record('settings_updated', "Deleted Team: {$name}");
        return redirect()->route('settings.index')->with('success', 'Team deleted successfully!');
    }

    // Users Management
    public function storeUser(Request $request)
    {
        $this->checkAccess();
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:admin,team_leader,staff',
            'designation_id' => 'nullable|exists:designations,id',
        ]);

        $newUser = User::create([
            'tenant_id' => $this->resolveTenant()?->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'designation_id' => $request->designation_id,
        ]);

        ActivityLog::record('staff_created', "Added Staff Member: {$newUser->name} ({$newUser->role})", null, [
            'role' => ['old' => 'None', 'new' => $newUser->role],
            'email' => ['old' => 'None', 'new' => $newUser->email],
        ]);

        return redirect()->route('settings.index')->with('success', 'User/Staff added successfully!');
    }

    // Shifts
    public function storeShift(Request $request)
    {
        $this->checkAccess();
        $request->validate([
            'name' => 'required|string|max:100',
            'start_time' => 'nullable|string|max:20',
            'end_time' => 'nullable|string|max:20',
        ]);

        Shift::create([
            'tenant_id' => $this->resolveTenant()?->id,
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        ActivityLog::record('settings_updated', "Added Shift: {$request->name}");

        return redirect()->route('settings.index')->with('success', 'Shift added successfully!');
    }

    public function destroyShift(Shift $shift)
    {
        $this->checkAccess();
        $name = $shift->name;
        $shift->delete();
        ActivityLog::record('settings_updated', "Deleted Shift: {$name}");
        return redirect()->route('settings.index')->with('success', 'Shift deleted successfully!');
    }

    /**
     * Update Slack and Telegram Webhook Configuration settings.
     */
    public function updateWebhookSettings(Request $request)
    {
        $this->checkAccess();

        $request->validate([
            'slack_webhook_url' => 'nullable|url|max:2048',
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_chat_id' => 'nullable|string|max:255',
        ]);

        $tenant = $this->resolveTenant();
        if ($tenant) {
            $tenant->update([
                'slack_webhook_url' => $request->slack_webhook_url,
                'telegram_bot_token' => $request->telegram_bot_token,
                'telegram_chat_id' => $request->telegram_chat_id,
            ]);
            ActivityLog::record('webhooks_updated', 'Updated Slack & Telegram Webhook settings');
            return redirect()->route('settings.index')->with('success', 'Messaging Webhook settings updated successfully!');
        }

        return redirect()->route('settings.index')->with('error', 'No tenant associated with this account.');
    }

    /**
     * Store a newly created custom designation.
     */
    public function storeDesignation(Request $request)
    {
        $this->checkAccess();
        $allowedPerms = implode(',', array_keys(self::AVAILABLE_PERMISSIONS));
        $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:7',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:' . $allowedPerms,
        ]);

        $designation = \App\Models\Designation::create([
            'tenant_id' => $this->resolveTenant()?->id,
            'name' => $request->name,
            'color' => $request->color ?? '#e2e8f0',
            'permissions' => $request->permissions ?? ['view_tasks', 'create_tasks', 'quick_update_links', 'view_calendar'],
        ]);

        ActivityLog::record('designation_created', "Created Designation: {$designation->name}");

        return redirect()->route('settings.index')->with('success', 'Designation added successfully!');
    }

    /**
     * Update permissions for an existing designation.
     */
    public function updateDesignationPermissions(Request $request, \App\Models\Designation $designation)
    {
        $this->checkAccess();
        $allowedPerms = implode(',', array_keys(self::AVAILABLE_PERMISSIONS));
        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:' . $allowedPerms,
        ]);

        $oldPermissions = $designation->permissions ?? [];
        $newPermissions = $request->permissions ?? [];

        $designation->update([
            'permissions' => $newPermissions,
        ]);

        ActivityLog::record('permissions_updated', "Updated permissions for {$designation->name}", null, [
            'permissions' => [
                'old' => implode(', ', $oldPermissions) ?: 'Default',
                'new' => implode(', ', $newPermissions) ?: 'Default',
            ],
        ]);

        return redirect()->route('settings.index')->with('success', 'Designation permissions updated successfully!');
    }

    /**
     * Delete a custom designation.
     */
    public function destroyDesignation(\App\Models\Designation $designation)
    {
        $this->checkAccess();
        $name = $designation->name;
        $designation->delete();
        ActivityLog::record('designation_deleted', "Deleted Designation: {$name}");
        return redirect()->route('settings.index')->with('success', 'Designation deleted successfully!');
    }

    /**
     * Save YouTube and Facebook API credentials and sync interval frequency.
     */
    public function updateApiSettings(Request $request)
    {
        $this->checkAccess();

        $request->validate([
            'youtube_api_key' => 'nullable|string|max:255',
            'facebook_page_access_token' => 'nullable|string|max:2048',
            'api_sync_interval' => 'required|integer|in:0,6,12,24',
        ]);

        $tenant = $this->resolveTenant();
        if ($tenant) {
            $tenant->update([
                'youtube_api_key' => $request->youtube_api_key,
                'facebook_page_access_token' => $request->facebook_page_access_token,
                'api_sync_interval' => (int)$request->api_sync_interval,
            ]);
            ActivityLog::record('api_keys_updated', 'Updated Social API Integration credentials & sync interval');
            return redirect()->route('settings.index')->with('success', 'Social API Integration settings updated successfully!');
        }

        return redirect()->route('settings.index')->with('error', 'No tenant associated with this account.');
    }

    /**
     * Trigger manual bulk synchronization for all recent tasks.
     */
    public function triggerBulkSync()
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isSuperAdmin() && !$user->hasPermission('sync_social_metrics') && !$user->hasPermission('manage_settings')) {
            abort(403, 'Unauthorized access.');
        }

        $tenant = $this->resolveTenant();
        if (!$tenant) {
            return redirect()->route('settings.index')->with('error', 'No tenant associated with this account.');
        }

        if (!$tenant->youtube_api_key && !$tenant->facebook_page_access_token) {
            return redirect()->route('settings.index')->with('error', 'Please configure your API keys first.');
        }

        try {
            $syncService = new \App\Services\SocialMediaSyncService();
            $syncedCount = $syncService->syncTenantTasks($tenant, 7);

            $tenant->update(['last_api_sync_at' => now()]);
            ActivityLog::record('settings_updated', "Triggered bulk social metrics sync (Updated {$syncedCount} tasks)");

            return redirect()->route('settings.index')->with('success', "Bulk sync completed successfully! Sync processed recent tasks (Updated: {$syncedCount}).");
        } catch (\Exception $e) {
            return redirect()->route('settings.index')->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Toggle whether a designation appears as a dynamic column on the Task table.
     */
    public function toggleTaskColumn(\App\Models\Designation $designation)
    {
        $this->checkAccess();
        
        $designation->update([
            'is_task_column' => !$designation->is_task_column
        ]);

        $status = $designation->is_task_column ? 'visible as a task column' : 'hidden from task columns';
        ActivityLog::record('column_toggled', "Toggled task column for {$designation->name}: {$status}");
        return redirect()->route('settings.index')->with('success', "Designation '{$designation->name}' is now {$status}!");
    }

    /**
     * Update Task Form Fields Visibility & Validation Requirements Configuration
     */
    public function updateTaskFormConfig(\Illuminate\Http\Request $request)
    {
        $this->checkAccess();
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            return redirect()->route('settings.index')->with('error', 'No tenant associated.');
        }

        $fieldsConfig = [];
        $fieldsInput = $request->input('fields', []);

        foreach ($fieldsInput as $fieldKey => $fieldData) {
            $visible = isset($fieldData['visible']) && $fieldData['visible'] == '1';
            $required = isset($fieldData['required']) && $fieldData['required'] == '1';
            $sortOrder = isset($fieldData['sort_order']) ? (int)$fieldData['sort_order'] : 99;
            $isCustom = isset($fieldData['is_custom']) && $fieldData['is_custom'] == '1';
            $label = isset($fieldData['label']) ? strip_tags($fieldData['label']) : '';
            $type = isset($fieldData['type']) ? $fieldData['type'] : 'text';

            if ($required) {
                $visible = true;
            }

            $fieldsConfig[$fieldKey] = [
                'visible' => $visible,
                'required' => $required,
                'sort_order' => $sortOrder,
                'is_custom' => $isCustom,
                'label' => $label,
                'type' => $type,
            ];
        }

        $tenant->update(['task_form_config' => $fieldsConfig]);
        ActivityLog::record('form_config_updated', 'Updated Dynamic Task Form layout & validation configuration');

        return redirect()->route('settings.index')->with('success', 'Task form layout configuration updated successfully!');
    }
}

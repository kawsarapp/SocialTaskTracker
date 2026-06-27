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
    private function checkAccess()
    {
        $role = Auth::user()->role;
        if ($role !== 'admin' && $role !== 'super_admin') {
            abort(403, 'Unauthorized access.');
        }
    }

    public function index()
    {
        $this->checkAccess();

        $tenantId = Auth::user()->tenant_id;

        // Fetch scoped items
        $taskTypes = TaskType::all();
        $facebookPages = FacebookPage::all();
        $youtubeChannels = YoutubeChannel::all();
        $areas = Area::all();
        $teams = Team::all();
        $users = User::with('designation')->where('tenant_id', $tenantId)->get();
        $shifts = Shift::all();
        $designations = \App\Models\Designation::all();
        $activityLogs = ActivityLog::with('user')->orderBy('created_at', 'desc')->take(50)->get();

        return view('settings.index', compact('taskTypes', 'facebookPages', 'youtubeChannels', 'areas', 'teams', 'users', 'shifts', 'designations', 'activityLogs'));
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
            'name' => $request->name,
            'color' => $request->color ?? '#3490dc',
        ]);

        return redirect()->route('settings.index')->with('success', 'Task Type added successfully!');
    }

    public function destroyTaskType(TaskType $taskType)
    {
        $this->checkAccess();
        $taskType->delete();
        return redirect()->route('settings.index')->with('success', 'Task Type deleted successfully!');
    }

    // FB Pages
    public function storeFbPage(Request $request)
    {
        $this->checkAccess();
        $request->validate(['name' => 'required|string|max:100']);

        FacebookPage::create(['name' => $request->name, 'is_active' => true]);
        return redirect()->route('settings.index')->with('success', 'Facebook Page added successfully!');
    }

    public function destroyFbPage(FacebookPage $facebookPage)
    {
        $this->checkAccess();
        $facebookPage->delete();
        return redirect()->route('settings.index')->with('success', 'Facebook Page deleted successfully!');
    }

    // YT Channels
    public function storeYtChannel(Request $request)
    {
        $this->checkAccess();
        $request->validate(['name' => 'required|string|max:100']);

        YoutubeChannel::create(['name' => $request->name, 'is_active' => true]);
        return redirect()->route('settings.index')->with('success', 'YouTube Channel added successfully!');
    }

    public function destroyYtChannel(YoutubeChannel $youtubeChannel)
    {
        $this->checkAccess();
        $youtubeChannel->delete();
        return redirect()->route('settings.index')->with('success', 'YouTube Channel deleted successfully!');
    }

    // Areas
    public function storeArea(Request $request)
    {
        $this->checkAccess();
        $request->validate(['name' => 'required|string|max:100']);

        Area::create(['name' => $request->name]);
        return redirect()->route('settings.index')->with('success', 'Area/News Source added successfully!');
    }

    public function destroyArea(Area $area)
    {
        $this->checkAccess();
        $area->delete();
        return redirect()->route('settings.index')->with('success', 'Area deleted successfully!');
    }

    // Teams
    public function storeTeam(Request $request)
    {
        $this->checkAccess();
        $request->validate(['name' => 'required|string|max:100']);

        Team::create(['name' => $request->name]);
        return redirect()->route('settings.index')->with('success', 'Team added successfully!');
    }

    public function destroyTeam(Team $team)
    {
        $this->checkAccess();
        $team->delete();
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

        User::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'designation_id' => $request->designation_id,
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
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return redirect()->route('settings.index')->with('success', 'Shift added successfully!');
    }

    public function destroyShift(Shift $shift)
    {
        $this->checkAccess();
        $shift->delete();
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

        $tenant = Auth::user()->tenant;
        if ($tenant) {
            $tenant->update([
                'slack_webhook_url' => $request->slack_webhook_url,
                'telegram_bot_token' => $request->telegram_bot_token,
                'telegram_chat_id' => $request->telegram_chat_id,
            ]);
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
        $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:7',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:view_tasks,create_tasks,edit_all_tasks,delete_tasks,view_analytics',
        ]);

        \App\Models\Designation::create([
            'name' => $request->name,
            'color' => $request->color ?? '#e2e8f0',
            'permissions' => $request->permissions ?? [],
        ]);

        return redirect()->route('settings.index')->with('success', 'Designation added successfully!');
    }

    /**
     * Update permissions for an existing designation.
     */
    public function updateDesignationPermissions(Request $request, \App\Models\Designation $designation)
    {
        $this->checkAccess();
        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:view_tasks,create_tasks,edit_all_tasks,delete_tasks,view_analytics',
        ]);

        $designation->update([
            'permissions' => $request->permissions ?? [],
        ]);

        return redirect()->route('settings.index')->with('success', 'Designation permissions updated successfully!');
    }

    /**
     * Delete a custom designation.
     */
    public function destroyDesignation(\App\Models\Designation $designation)
    {
        $this->checkAccess();
        $designation->delete();
        return redirect()->route('settings.index')->with('success', 'Designation deleted successfully!');
    }
}

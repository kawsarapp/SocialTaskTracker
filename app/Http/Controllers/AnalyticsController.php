<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    private function checkAccess()
    {
        $role = Auth::user()->role;
        if ($role !== 'admin' && $role !== 'team_leader' && $role !== 'super_admin') {
            abort(403, 'Unauthorized access.');
        }
    }

    /**
     * Display a visual dashboard of production metrics.
     */
    public function index()
    {
        $this->checkAccess();
        $tenantId = Auth::user()->tenant_id;

        // 1. Content Type breakdown (Doughnut Chart)
        $taskTypeStats = TaskType::all()->map(function ($type) use ($tenantId) {
            $count = Task::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('task_type_id', $type->id)
                ->count();
            return [
                'name' => $type->name,
                'count' => $count,
                'color' => $type->color ?? '#4f46e5',
            ];
        })->filter(fn($item) => $item['count'] > 0)->values();

        // 2. Shift breakdown (Line / Bar Chart)
        $shiftStats = Shift::where('tenant_id', $tenantId)->get()->map(function ($shift) use ($tenantId) {
            $count = Task::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('shift_id', $shift->id)
                ->count();
            return [
                'name' => $shift->name,
                'count' => $count,
            ];
        });
        
        // Add "No Shift / General" count
        $noShiftCount = Task::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNull('shift_id')
            ->count();
        if ($noShiftCount > 0) {
            $shiftStats->push([
                'name' => 'No Shift / General',
                'count' => $noShiftCount
            ]);
        }

        // 3. Platform breakdown (Facebook vs YouTube counts)
        $fbOnly = Task::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('facebook_url')
            ->where('facebook_url', '<>', '')
            ->where(function($q) {
                $q->whereNull('youtube_url')->orWhere('youtube_url', '');
            })->count();

        $ytOnly = Task::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('youtube_url')
            ->where('youtube_url', '<>', '')
            ->where(function($q) {
                $q->whereNull('facebook_url')->orWhere('facebook_url', '');
            })->count();

        $bothPlatforms = Task::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('facebook_url')
            ->where('facebook_url', '<>', '')
            ->whereNotNull('youtube_url')
            ->where('youtube_url', '<>', '')
            ->count();

        $noLink = Task::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where(function($q) {
                $q->whereNull('facebook_url')->orWhere('facebook_url', '');
            })
            ->where(function($q) {
                $q->whereNull('youtube_url')->orWhere('youtube_url', '');
            })->count();

        $platformStats = [
            ['name' => 'Facebook Only', 'count' => $fbOnly, 'color' => '#1877f2'],
            ['name' => 'YouTube Only', 'count' => $ytOnly, 'color' => '#ff0000'],
            ['name' => 'Both Platforms', 'count' => $bothPlatforms, 'color' => '#10b981'],
            ['name' => 'Pending Links', 'count' => $noLink, 'color' => '#f59e0b'],
        ];

        // 4. Staff Performance statistics (Grouped Bar Chart)
        $users = User::where('tenant_id', $tenantId)->get();
        $staffStats = $users->map(function ($user) use ($tenantId) {
            $scripts = Task::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('script_by', $user->id)->count();
            $voices = Task::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('voice_by', $user->id)->count();
            $edits = Task::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('video_edit_by', $user->id)->count();
            
            return [
                'name' => $user->name,
                'scripts' => $scripts,
                'voices' => $voices,
                'edits' => $edits,
                'total' => $scripts + $voices + $edits,
            ];
        })->filter(fn($u) => $u['total'] > 0)->sortByDesc('total')->values();

        // 5. Total counts summary cards
        $summary = [
            'total_tasks' => Task::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'total_staff' => $users->count(),
            'total_areas' => Area::where('tenant_id', $tenantId)->count(),
            'total_shifts' => Shift::where('tenant_id', $tenantId)->count(),
        ];

        return view('analytics.index', compact('taskTypeStats', 'shiftStats', 'platformStats', 'staffStats', 'summary'));
    }
}

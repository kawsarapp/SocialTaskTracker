<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AttendanceController extends Controller
{
    /**
     * Handle user Check-In.
     */
    public function checkIn(Request $request)
    {
        $userId = Auth::id();
        $today = now()->format('Y-m-d');

        // Check if an entry already exists for today
        $attendance = Attendance::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        if ($attendance) {
            if ($attendance->status === 'leave') {
                return redirect()->back()->with('error', 'You cannot check in because you are marked as ON LEAVE today.');
            }
            return redirect()->back()->with('error', 'You have already checked in today.');
        }

        Attendance::create([
            'tenant_id' => Auth::user()->effectiveTenantId(),
            'user_id' => $userId,
            'date' => $today,
            'check_in' => now(),
            'status' => 'present',
        ]);

        ActivityLog::record('attendance_checkin', Auth::user()->name . ' checked in (' . now()->format('h:i A') . ')');

        return redirect()->back()->with('success', 'Checked in successfully!');
    }

    /**
     * Handle user Check-Out.
     */
    public function checkOut(Request $request)
    {
        $userId = Auth::id();
        $today = now()->format('Y-m-d');

        $attendance = Attendance::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return redirect()->back()->with('error', 'You must check in first before checking out.');
        }

        if ($attendance->check_out) {
            return redirect()->back()->with('error', 'You have already checked out today.');
        }

        $attendance->update([
            'check_out' => now(),
        ]);

        ActivityLog::record('attendance_checkout', Auth::user()->name . ' checked out (' . now()->format('h:i A') . ')');

        return redirect()->back()->with('success', 'Checked out successfully!');
    }

    /**
     * Store a leave entry for a staff member (Admin/Team Leader action).
     */
    public function storeLeave(Request $request)
    {
        // Enforce role authorization (Only Admins, Team Leaders, or manage_settings)
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isTeamLeader() && !$user->isSuperAdmin() && !$user->hasPermission('manage_settings')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'remarks' => 'required|string|max:500',
        ]);

        $staffUser = User::find($request->user_id);
        if (!$staffUser || ($staffUser->tenant_id !== $user->effectiveTenantId() && !$user->isSuperAdmin())) {
            abort(403, 'Unauthorized access to user from another tenant.');
        }

        // Find or create leave record for the date
        Attendance::updateOrCreate(
            [
                'tenant_id' => $staffUser?->tenant_id ?? $user->effectiveTenantId(),
                'user_id' => $request->user_id,
                'date' => $request->date,
            ],
            [
                'status' => 'leave',
                'remarks' => $request->remarks,
                'check_in' => null,
                'check_out' => null,
            ]
        );

        $staffName = $staffUser?->name ?? "User #{$request->user_id}";
        ActivityLog::record('leave_requested', "Marked {$staffName} on leave for {$request->date}");

        return redirect()->back()->with('success', 'Staff marked on leave successfully.');
    }
}

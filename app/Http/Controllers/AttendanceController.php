<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
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
            'tenant_id' => Auth::user()->tenant_id,
            'user_id' => $userId,
            'date' => $today,
            'check_in' => now(),
            'status' => 'present',
        ]);

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

        return redirect()->back()->with('success', 'Checked out successfully!');
    }

    /**
     * Store a leave entry for a staff member (Admin/Team Leader action).
     */
    public function storeLeave(Request $request)
    {
        // Enforce role authorization (Only Admins and Team Leaders)
        $user = Auth::user();
        if ($user->role !== 'admin' && $user->role !== 'team_leader' && !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'remarks' => 'required|string|max:500',
        ]);

        // Find or create leave record for the date
        Attendance::updateOrCreate(
            [
                'tenant_id' => $user->tenant_id,
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

        return redirect()->back()->with('success', 'Staff marked on leave successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        $tenants = Tenant::withCount(['users', 'tasks'])->get();
        return view('superadmin.index', compact('tenants'));
    }

    public function storeTenant(Request $request)
    {
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:tenants,slug|max:255|alpha_dash',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|string|email|lowercase|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8',
        ]);

        // 1. Create Tenant
        $tenant = Tenant::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'is_active' => true,
        ]);

        // 2. Seed Default Designations
        $defaultDesignations = [
            'Reporter' => '#e0f2fe',
            'Script Writer' => '#fef3c7',
            'Uploader' => '#e0e7ff',
            'Voice Artist' => '#f3e8ff',
            'Correspondent' => '#dcfce7',
            'Content Reviewer' => '#ffedd5',
            'Video Editor' => '#ccfbf1',
            'Graphic Designer' => '#fce7f3',
            'Sound Engineer' => '#f1f5f9',
            'Researcher' => '#fae8ff',
            'Content Strategist' => '#e0f2fe',
            'SEO Specialist' => '#fef2f2'
        ];
        $columnsToEnable = ['Script Writer', 'Voice Artist', 'Video Editor', 'Graphic Designer', 'Uploader'];
        foreach ($defaultDesignations as $name => $color) {
            \App\Models\Designation::create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'color' => $color,
                'is_task_column' => in_array($name, $columnsToEnable),
                'permissions' => ['view_tasks', 'create_tasks', 'quick_update_links', 'view_calendar'],
            ]);
        }

        // 3. Seed Default Task Types
        $defaultTypes = [
            'PR' => '#e2e8f0', 'Reels' => '#fbcfe8', 'NWS' => '#fef3c7', 'PKG' => '#dbeafe', 
            'OOV' => '#e0f2fe', 'LIVE' => '#fee2e2', 'PGM' => '#f3e8ff', 'Clip' => '#ccfbf1', 
            'UNCUT' => '#f5f5f4', 'SPL' => '#ffedd5', 'SOT' => '#ecfccb'
        ];
        foreach ($defaultTypes as $name => $color) {
            \App\Models\TaskType::create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'color' => $color,
            ]);
        }

        // 4. Seed Default Shifts
        $defaultShifts = ['Morning', 'Evening', 'Night'];
        foreach ($defaultShifts as $shiftName) {
            \App\Models\Shift::create([
                'tenant_id' => $tenant->id,
                'name' => $shiftName,
            ]);
        }

        // 5. Create Owner/Admin User
        \App\Models\User::create([
            'tenant_id' => $tenant->id,
            'name' => $request->admin_name,
            'email' => $request->admin_email,
            'password' => \Hash::make($request->admin_password),
            'role' => 'admin',
        ]);

        return redirect()->route('superadmin.index')->with('success', 'Organization tenant and Owner Admin account created successfully with defaults seeded!');
    }

    public function toggleTenant(Tenant $tenant)
    {
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        $tenant->update([
            'is_active' => !$tenant->is_active
        ]);

        return redirect()->route('superadmin.index')->with('success', 'Tenant status updated successfully!');
    }
}

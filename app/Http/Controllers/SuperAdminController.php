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
        ]);

        Tenant::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'is_active' => true,
        ]);

        return redirect()->route('superadmin.index')->with('success', 'Organization tenant created successfully!');
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

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'org_name' => ['required', 'string', 'max:255'],
            'org_slug' => ['required', 'string', 'lowercase', 'max:255', 'unique:\App\Models\Tenant,slug', 'alpha_dash'],
        ]);

        // 1. Create Tenant
        $tenant = \App\Models\Tenant::create([
            'name' => $request->org_name,
            'slug' => $request->org_slug,
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

        // 5. Create Admin User
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $users = \DB::table('users')->whereNull('tenant_id')->where('role', '<>', 'super_admin')->get();

        foreach ($users as $user) {
            // Find a suitable tenant
            $emailParts = explode('@', $user->email);
            $prefix = $emailParts[0] ?? 'default';
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $prefix));

            // Try to match an existing tenant by slug or prefix
            $tenant = \DB::table('tenants')->where('slug', $slug)->first();
            if (!$tenant) {
                // Try to find a tenant containing the prefix (e.g. "starnews" for "starnewsbd")
                $tenant = \DB::table('tenants')->where('slug', 'like', '%' . substr($slug, 0, 5) . '%')->first();
            }

            // Fallback: If still no tenant, check if any tenant exists at all
            if (!$tenant) {
                $tenant = \DB::table('tenants')->first();
            }

            // Fallback: Create a new tenant if database is completely empty
            if (!$tenant) {
                $tenantId = \DB::table('tenants')->insertGetId([
                    'name' => ucfirst($prefix),
                    'slug' => $slug,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $tenantId = $tenant->id;
            }

            // Link the user to the tenant
            \DB::table('users')->where('id', $user->id)->update([
                'tenant_id' => $tenantId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};

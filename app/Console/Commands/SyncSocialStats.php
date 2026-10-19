<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:sync-social-stats')]
#[Description('Sync Facebook and YouTube views/likes statistics for tenants based on their configured quota intervals.')]
class SyncSocialStats extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenants = \App\Models\Tenant::where('is_active', true)->get();
        $this->info("Starting social media statistics sync command...");

        foreach ($tenants as $tenant) {
            $interval = (int)$tenant->api_sync_interval;
            
            // 1. Skip if set to Manual Sync Only (0)
            if ($interval === 0) {
                $this->line("Tenant '{$tenant->name}' (ID: {$tenant->id}) is set to Manual Sync Only. Skipping.");
                continue;
            }

            // 2. Check if enough hours have passed since last sync
            $lastSync = $tenant->last_api_sync_at;
            if ($lastSync) {
                $hoursDiff = now()->diffInHours($lastSync);
                if ($hoursDiff < $interval) {
                    $this->line("Tenant '{$tenant->name}' was synced {$hoursDiff} hours ago (Interval: {$interval}h). Skipping.");
                    continue;
                }
            }

            $this->info("Syncing stats for tenant: '{$tenant->name}' (Interval: {$interval}h)...");

            try {
                $syncService = new \App\Services\SocialMediaSyncService();
                $syncedCount = $syncService->syncTenantTasks($tenant, 7);

                // Update last sync time
                $tenant->update(['last_api_sync_at' => now()]);

                $this->info("Successfully synced {$syncedCount} tasks for tenant '{$tenant->name}'.");
            } catch (\Exception $e) {
                $this->error("Failed to sync stats for tenant '{$tenant->name}': " . $e->getMessage());
            }
        }

        $this->info("Social media stats sync process completed.");
    }
}

<?php

namespace App\Console\Commands;

use App\Mail\DailySummaryMail;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailySummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-daily-summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily work performance and missing link summaries to tenant leaders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Daily Summary email process...');
        
        $tenants = Tenant::where('is_active', true)->get();
        $todayStr = date('Y-m-d');
        
        foreach ($tenants as $tenant) {
            $this->info("Processing Tenant: {$tenant->name} (#{$tenant->id})");
            
            // 1. Fetch all tasks on-air today for this tenant
            $tasks = Task::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereDate('on_air_date', $todayStr)
                ->with(['taskType', 'entryUser', 'scriptWriter', 'voiceArtist', 'videoEditor', 'shift'])
                ->get();
                
            $this->info("Found " . $tasks->count() . " tasks for today.");

            // 2. Count by Task Type
            $stats = [];
            foreach ($tasks as $task) {
                $typeName = $task->taskType->name ?? 'Unknown';
                $stats[$typeName] = ($stats[$typeName] ?? 0) + 1;
            }

            // 2.5 Count by Shift
            $shiftStats = [];
            foreach ($tasks as $task) {
                $shiftName = $task->shift->name ?? 'No Shift / General';
                $shiftStats[$shiftName] = ($shiftStats[$shiftName] ?? 0) + 1;
            }

            // 3. Count staff performance
            $staffStats = [];
            $users = User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->get();
            foreach ($users as $user) {
                $staffStats[$user->name] = ['scripts' => 0, 'voices' => 0, 'edits' => 0];
            }
            foreach ($tasks as $task) {
                if ($task->scriptWriter) {
                    $staffStats[$task->scriptWriter->name]['scripts']++;
                }
                if ($task->voiceArtist) {
                    $staffStats[$task->voiceArtist->name]['voices']++;
                }
                if ($task->videoEditor) {
                    $staffStats[$task->videoEditor->name]['edits']++;
                }
            }
            // Remove staff with zero logged work to keep reports concise
            $staffStats = array_filter($staffStats, function($item) {
                return $item['scripts'] > 0 || $item['voices'] > 0 || $item['edits'] > 0;
            });

            // 4. Find tasks with missing URLs
            $pendingLinks = $tasks->filter(function($task) {
                return empty($task->facebook_url) || empty($task->youtube_url);
            });

            // 4.5 Fetch staff members on leave today
            $leaves = \App\Models\Attendance::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereDate('date', $todayStr)
                ->where('status', 'leave')
                ->with('user')
                ->get()
                ->pluck('user.name')
                ->toArray();

            // 5. Gather recipients (Admins and Team Leaders of this organization)
            $recipients = User::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereIn('role', ['admin', 'team_leader'])
                ->get();

            if ($recipients->isNotEmpty()) {
                $emails = $recipients->pluck('email')->toArray();
                $this->info("Sending summary to: " . implode(', ', $emails));
                
                try {
                    Mail::to($emails)->send(new DailySummaryMail(
                        $tenant->name,
                        date('d/m/Y'),
                        $stats,
                        $staffStats,
                        $pendingLinks,
                        $leaves,
                        $shiftStats
                    ));
                    $this->info("Daily Summary Mail sent successfully for {$tenant->name}.");
                } catch (\Exception $e) {
                    $this->error("Failed to send email for {$tenant->name}: " . $e->getMessage());
                }
            } else {
                $this->warn("No admin or team leader found for tenant {$tenant->name}. Skipping email.");
            }
        }

        $this->info('Daily Summary email process completed.');
    }
}

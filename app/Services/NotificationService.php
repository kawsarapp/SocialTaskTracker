<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Dispatch messaging alerts to Slack and Telegram based on Tenant settings.
     */
    public static function sendTaskNotification(Task $task, string $action)
    {
        try {
            // Load tenant bypassing global scopes
            $tenant = Tenant::withoutGlobalScopes()->find($task->tenant_id);
            if (!$tenant) {
                return;
            }

            $hasSlack = !empty($tenant->slack_webhook_url);
            $hasTelegram = !empty($tenant->telegram_bot_token) && !empty($tenant->telegram_chat_id);

            // If no webhooks configured, skip
            if (!$hasSlack && !$hasTelegram) {
                return;
            }

            // Load relations if not loaded
            $task->load(['taskType', 'shift', 'scriptWriter', 'voiceArtist', 'videoEditor', 'entryUser', 'assignments.designation', 'assignments.user']);

            $emoji = $action === 'created' ? '🆕' : '🚀';
            $titleAction = $action === 'created' ? 'New Content Logged' : 'Task Published Live';

            // Format markdown message
            $msg = "*{$emoji} {$titleAction}* [{$tenant->name}]\n";
            $msg .= "------------------------------------\n";
            $msg .= "*Task ID:* #{$task->id}\n";
            $msg .= "*Title:* {$task->title}\n";
            $msg .= "*Type:* " . ($task->taskType->name ?? 'Unknown') . "\n";
            $msg .= "*Shift:* " . ($task->shift->name ?? 'General') . "\n";
            $msg .= "*On Air:* " . ($task->on_air_date?->format('d/m/Y') ?? 'N/A') . " at " . ($task->on_air_time ?? 'N/A') . "\n";
            $msg .= "*Logged By:* " . ($task->entryUser->name ?? 'System') . "\n\n";

            $msg .= "*Staff Assignments:*\n";
            if ($task->assignments->isNotEmpty()) {
                foreach ($task->assignments as $assignment) {
                    $roleName = $assignment->designation->name ?? 'Staff';
                    $userName = $assignment->user->name ?? 'Not Assigned';
                    $msg .= "  - {$roleName}: {$userName}\n";
                }
                $msg .= "\n";
            } else {
                $msg .= "  - Script: " . ($task->scriptWriter->name ?? 'Not Assigned') . "\n";
                $msg .= "  - Voice: " . ($task->voiceArtist->name ?? 'Not Assigned') . "\n";
                $msg .= "  - Video Edit: " . ($task->videoEditor->name ?? 'Not Assigned') . "\n\n";
            }

            $msg .= "*Social Publishing Links:*\n";
            $msg .= "  - Facebook: " . ($task->facebook_url ?: 'Pending ✖') . "\n";
            $msg .= "  - YouTube: " . ($task->youtube_url ?: 'Pending ✖') . "\n";

            if ($task->remarks) {
                $msg .= "\n*Remarks:* {$task->remarks}\n";
            }

            // 1. Send Slack webhook
            if ($hasSlack) {
                Http::timeout(5)->post($tenant->slack_webhook_url, [
                    'text' => str_replace('*', '', $msg) // Slack uses raw text usually or mrkdwn, let's pass clean string
                ]);
            }

            // 2. Send Telegram notification
            if ($hasTelegram) {
                $telegramUrl = "https://api.telegram.org/bot{$tenant->telegram_bot_token}/sendMessage";
                Http::timeout(5)->post($telegramUrl, [
                    'chat_id' => $tenant->telegram_chat_id,
                    'text' => $msg,
                    'parse_mode' => 'Markdown',
                ]);
            }
        } catch (\Exception $e) {
            // Keep app safe by only logging failures silently
            Log::error("NotificationService Error: " . $e->getMessage());
        }
    }
}

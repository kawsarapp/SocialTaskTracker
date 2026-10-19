<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\Designation;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskType;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSheetImportService
{
    /**
     * Import a single task row into a tenant.
     */
    public function importSingleRow(Tenant $tenant, array $rowData, string $source = 'webhook'): ?Task
    {
        // 1. Normalize array keys
        $normalizedRow = [];
        foreach ($rowData as $key => $value) {
            $normKey = strtolower(trim((string) $key));
            $normKey = preg_replace('/[^a-z0-9]+/', '_', $normKey);
            $normKey = trim($normKey, '_');
            $normalizedRow[$normKey] = is_string($value) ? trim($value) : $value;
        }

        // 2. Apply Tenant Column Mapping if defined
        if (!empty($tenant->google_sheet_column_mapping) && is_array($tenant->google_sheet_column_mapping)) {
            foreach ($tenant->google_sheet_column_mapping as $targetField => $sheetCol) {
                $targetFieldNorm = strtolower(trim((string) $targetField));
                $targetFieldNorm = trim(preg_replace('/[^a-z0-9]+/', '_', $targetFieldNorm), '_');

                $sheetColNorm = strtolower(trim((string) $sheetCol));
                $sheetColNorm = trim(preg_replace('/[^a-z0-9]+/', '_', $sheetColNorm), '_');

                if (isset($normalizedRow[$sheetColNorm]) && !isset($normalizedRow[$targetFieldNorm])) {
                    $normalizedRow[$targetFieldNorm] = $normalizedRow[$sheetColNorm];
                }
            }
        }

        // 3. Extract Title (Required)
        $title = $normalizedRow['title'] 
            ?? $normalizedRow['task_title'] 
            ?? $normalizedRow['topic'] 
            ?? $normalizedRow['headline'] 
            ?? null;

        if (empty($title)) {
            return null;
        }
        $title = (string) $title;

        // 4. Parse On-Air Date & Time
        $rawDate = $normalizedRow['on_air_date'] ?? $normalizedRow['date'] ?? $normalizedRow['air_date'] ?? null;
        $onAirDate = now()->toDateString();
        if (!empty($rawDate)) {
            try {
                $onAirDate = Carbon::parse($rawDate)->toDateString();
            } catch (\Throwable $e) {
                $onAirDate = now()->toDateString();
            }
        }

        $rawTime = $normalizedRow['on_air_time'] ?? $normalizedRow['time'] ?? $normalizedRow['air_time'] ?? null;
        $onAirTime = now()->format('H:i');
        if (!empty($rawTime)) {
            try {
                $onAirTime = Carbon::parse($rawTime)->format('H:i');
            } catch (\Throwable $e) {
                $onAirTime = (string) $rawTime;
            }
        }

        // 5. Check for Duplicate Task within Tenant (same title and date)
        $existingTask = Task::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('title', $title)
            ->whereDate('on_air_date', $onAirDate)
            ->first();

        if ($existingTask) {
            return $existingTask;
        }

        // 6. Resolve Entry By User
        $entryBy = Auth::id()
            ?? $tenant->users()->whereIn('role', ['admin', 'team_leader'])->value('id')
            ?? $tenant->users()->value('id')
            ?? User::whereIn('role', ['super_admin', 'admin'])->value('id')
            ?? User::value('id');

        // 7. Resolve Task Type
        $taskTypeId = null;
        $typeInput = $normalizedRow['task_type'] ?? $normalizedRow['task_type_id'] ?? $normalizedRow['type'] ?? $normalizedRow['category'] ?? null;
        if ($typeInput) {
            $taskType = TaskType::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where(function ($q) use ($typeInput) {
                    $q->where('name', 'LIKE', trim($typeInput))
                      ->orWhere('id', $typeInput);
                })
                ->first();
            $taskTypeId = $taskType?->id;
        }

        if (!$taskTypeId) {
            $taskType = TaskType::withoutGlobalScopes()->where('tenant_id', $tenant->id)->first();
            if (!$taskType) {
                $taskType = TaskType::create([
                    'tenant_id' => $tenant->id,
                    'name' => 'General',
                    'color' => '#3B82F6',
                ]);
            }
            $taskTypeId = $taskType->id;
        }

        // 8. Resolve Team
        $teamId = null;
        $teamInput = $normalizedRow['team'] ?? $normalizedRow['team_id'] ?? null;
        if ($teamInput) {
            $team = Team::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where(function ($q) use ($teamInput) {
                    $q->where('name', 'LIKE', trim($teamInput))
                      ->orWhere('id', $teamInput);
                })
                ->first();
            $teamId = $team?->id;
        }

        // 9. Resolve Area
        $areaId = null;
        $areaInput = $normalizedRow['area'] ?? $normalizedRow['area_id'] ?? null;
        if ($areaInput) {
            $area = Area::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where(function ($q) use ($areaInput) {
                    $q->where('name', 'LIKE', trim($areaInput))
                      ->orWhere('id', $areaInput);
                })
                ->first();
            $areaId = $area?->id;
        }

        // 10. Links & Details
        $facebookUrl = $normalizedRow['facebook_url'] ?? $normalizedRow['facebook'] ?? $normalizedRow['fb_url'] ?? null;
        $youtubeUrl = $normalizedRow['youtube_url'] ?? $normalizedRow['youtube'] ?? $normalizedRow['yt_url'] ?? null;
        $remarks = $normalizedRow['remarks'] ?? ('Imported from Google Sheets on ' . now()->toDateTimeString());
        $tags = $normalizedRow['tags'] ?? 'google-sheet';

        // 11. Create Task
        $task = Task::create([
            'tenant_id' => $tenant->id,
            'entry_by' => $entryBy,
            'title' => $title,
            'on_air_date' => $onAirDate,
            'on_air_time' => $onAirTime,
            'task_type_id' => $taskTypeId,
            'team_id' => $teamId,
            'area_id' => $areaId,
            'facebook_url' => $facebookUrl,
            'youtube_url' => $youtubeUrl,
            'remarks' => $remarks,
            'tags' => $tags,
        ]);

        // 12. Resolve Staff Assignments
        $this->resolveStaffAssignments($task, $tenant, $normalizedRow);

        // 13. Log Activity
        ActivityLog::record(
            'google_sheet_synced',
            'Imported task "' . $task->title . '" from Google Sheet (' . $source . ')',
            $task,
            ['source' => $source]
        );

        return $task;
    }

    /**
     * Resolve and assign staff to task based on row data keys.
     */
    protected function resolveStaffAssignments(Task $task, Tenant $tenant, array $row): void
    {
        $roleMappings = [
            'reporter' => 'Reporter',
            'script_writer' => 'Script Writer',
            'scriptwriter' => 'Script Writer',
            'script_by' => 'Script Writer',
            'voice_artist' => 'Voice Artist',
            'voiceartist' => 'Voice Artist',
            'voice_by' => 'Voice Artist',
            'video_editor' => 'Video Editor',
            'videoeditor' => 'Video Editor',
            'editor' => 'Video Editor',
            'video_edit_by' => 'Video Editor',
            'graphic_designer' => 'Graphic Designer',
            'uploader' => 'Uploader',
        ];

        // Process explicit role columns
        foreach ($roleMappings as $rowKey => $designationName) {
            if (!empty($row[$rowKey])) {
                $user = $this->findTenantUser($tenant, $row[$rowKey]);
                if ($user) {
                    $designation = $this->findOrCreateDesignation($tenant, $designationName);
                    TaskAssignment::updateOrCreate(
                        ['task_id' => $task->id, 'designation_id' => $designation->id],
                        ['user_id' => $user->id]
                    );
                }
            }
        }

        // Process 'assignments' array if provided
        if (!empty($row['assignments']) && is_array($row['assignments'])) {
            foreach ($row['assignments'] as $designationIdentifier => $userIdentifier) {
                if (empty($userIdentifier)) {
                    continue;
                }
                $user = $this->findTenantUser($tenant, $userIdentifier);
                if (!$user) {
                    continue;
                }

                $designation = null;
                if (is_numeric($designationIdentifier)) {
                    $designation = Designation::withoutGlobalScopes()
                        ->where('tenant_id', $tenant->id)
                        ->where('id', $designationIdentifier)
                        ->first();
                } else {
                    $designation = $this->findOrCreateDesignation($tenant, (string) $designationIdentifier);
                }

                if ($designation) {
                    TaskAssignment::updateOrCreate(
                        ['task_id' => $task->id, 'designation_id' => $designation->id],
                        ['user_id' => $user->id]
                    );
                }
            }
        }
    }

    /**
     * Find user belonging to the tenant by id, email, or name.
     */
    protected function findTenantUser(Tenant $tenant, mixed $identifier): ?User
    {
        if (empty($identifier)) {
            return null;
        }

        if (is_numeric($identifier)) {
            return User::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('id', $identifier)
                ->first();
        }

        $str = trim((string) $identifier);

        return User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where(function ($q) use ($str) {
                $q->where('email', $str)
                  ->orWhereRaw('LOWER(name) = ?', [strtolower($str)])
                  ->orWhere('name', 'LIKE', '%' . $str . '%');
            })
            ->first();
    }

    /**
     * Find or create designation for a tenant.
     */
    protected function findOrCreateDesignation(Tenant $tenant, string $name): Designation
    {
        $designation = Designation::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($name))])
            ->first();

        if (!$designation) {
            $designation = Designation::create([
                'tenant_id' => $tenant->id,
                'name' => trim($name),
                'is_task_column' => true,
            ]);
        }

        return $designation;
    }

    /**
     * Fetch published CSV and sync all rows into the tenant.
     */
    public function syncFromPublishedCsv(Tenant $tenant): array
    {
        if (empty($tenant->google_sheet_url)) {
            throw new \InvalidArgumentException('Google Sheet URL is not configured for this tenant.');
        }

        $csvUrl = $this->normalizeCsvUrl($tenant->google_sheet_url);

        $response = Http::timeout(15)->get($csvUrl);
        if (!$response->successful()) {
            throw new \RuntimeException("Failed to fetch Google Sheet CSV: HTTP {$response->status()}");
        }

        $content = $response->body();
        // Strip UTF-8 BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', trim($content));

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $header = null;
        $imported = 0;
        $skipped = 0;
        $total = 0;

        while (($row = fgetcsv($stream)) !== false) {
            if ($header === null) {
                $header = array_map(function ($col) {
                    $col = strtolower(trim((string) $col));
                    $col = preg_replace('/[^a-z0-9]+/', '_', $col);
                    return trim($col, '_');
                }, $row);
                continue;
            }

            // Check if entire row is empty
            if (empty(array_filter($row, fn($v) => !is_null($v) && trim((string) $v) !== ''))) {
                continue;
            }

            $rowData = [];
            foreach ($header as $i => $key) {
                if (!empty($key)) {
                    $rowData[$key] = isset($row[$i]) ? trim((string) $row[$i]) : null;
                }
            }

            $total++;
            $task = $this->importSingleRow($tenant, $rowData, 'csv_sync');

            if ($task && $task->wasRecentlyCreated) {
                $imported++;
            } else {
                $skipped++;
            }
        }

        fclose($stream);

        $tenant->update(['google_sheet_last_synced_at' => now()]);

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'total' => $total,
        ];
    }

    /**
     * Normalize various Google Sheet URL formats to their direct CSV export URL.
     */
    public function normalizeCsvUrl(string $url): string
    {
        $url = trim($url);

        if (str_contains($url, 'docs.google.com/spreadsheets')) {
            if (str_contains($url, 'format=csv') || str_contains($url, 'output=csv')) {
                return $url;
            }

            // Case: /pubhtml
            if (str_contains($url, '/pubhtml')) {
                return preg_replace('/\/pubhtml(\?.*)?$/', '/pub?output=csv', $url);
            }

            // Case: /pub without output=csv
            if (preg_match('/\/pub(\?.*)?$/', $url)) {
                $separator = str_contains($url, '?') ? '&' : '?';
                return $url . $separator . 'output=csv';
            }

            // Case: /edit
            if (str_contains($url, '/edit')) {
                $gid = null;
                if (preg_match('/[#&?]gid=([0-9]+)/', $url, $matches)) {
                    $gid = $matches[1];
                }
                $baseUrl = preg_replace('/\/edit.*$/', '', $url);
                $exportUrl = rtrim($baseUrl, '/') . '/export?format=csv';
                if ($gid) {
                    $exportUrl .= '&gid=' . $gid;
                }
                return $exportUrl;
            }

            // Fallback for spreadsheets root url
            return rtrim($url, '/') . '/export?format=csv';
        }

        return $url;
    }
}

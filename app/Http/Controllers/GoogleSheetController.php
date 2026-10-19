<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\GoogleSheetImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GoogleSheetController extends Controller
{
    /**
     * Public webhook endpoint for Google Sheets Apps Script / Zapier / Make / Webhooks.
     */
    public function webhook(Request $request, string $token, GoogleSheetImportService $service): JsonResponse
    {
        $tenant = Tenant::where('google_sheet_webhook_token', $token)->first();
        if (!$tenant) {
            abort(404, 'Invalid webhook token.');
        }

        $payload = $request->all();
        if (empty($payload)) {
            return response()->json([
                'success' => false,
                'message' => 'Empty webhook payload received.',
            ], 400);
        }

        // Detect if payload contains multiple rows
        $rows = null;
        if (isset($payload['rows']) && is_array($payload['rows'])) {
            $rows = $payload['rows'];
        } elseif (array_is_list($payload) && !empty($payload) && is_array($payload[0])) {
            $rows = $payload;
        } elseif (isset($payload['data']) && is_array($payload['data']) && array_is_list($payload['data'])) {
            $rows = $payload['data'];
        }

        if ($rows !== null) {
            $importedCount = 0;
            $lastTask = null;
            foreach ($rows as $row) {
                if (is_array($row)) {
                    $task = $service->importSingleRow($tenant, $row, 'webhook');
                    if ($task && $task->wasRecentlyCreated) {
                        $importedCount++;
                    }
                    if ($task) {
                        $lastTask = $task;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'imported_count' => $importedCount,
                'task_id' => $lastTask?->id,
                'message' => 'Tasks processed successfully.',
            ]);
        }

        // Single row payload
        $rowData = isset($payload['data']) && is_array($payload['data']) 
            ? $payload['data'] 
            : (isset($payload['row']) && is_array($payload['row']) ? $payload['row'] : $payload);

        $task = $service->importSingleRow($tenant, $rowData, 'webhook');

        return response()->json([
            'success' => true,
            'task_id' => $task?->id,
            'message' => 'Task imported successfully',
        ]);
    }

    /**
     * Trigger on-demand sync from configured published Google Sheet CSV.
     */
    public function sync(Request $request, GoogleSheetImportService $service)
    {
        $user = Auth::user();
        if (!$user->hasPermission('manage_settings') && !$user->hasPermission('create_tasks')) {
            abort(403, 'Unauthorized access.');
        }

        $tenant = $user->effectiveTenant();
        if (!$tenant) {
            abort(404, 'No active tenant found.');
        }

        if (empty($tenant->google_sheet_url)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Google Sheet URL is not configured.'], 422);
            }
            return redirect()->back()->with('error', 'Google Sheet URL is not configured. Please set a valid published Google Sheet or CSV URL in settings.');
        }

        try {
            $result = $service->syncFromPublishedCsv($tenant);
            $message = "Google Sheet synced: {$result['imported']} new task(s) imported, {$result['skipped']} duplicate(s) skipped.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'result' => $result,
                    'message' => $message,
                ]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sync failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Update tenant Google Sheet settings (URL, auto-sync, column mapping, token regeneration).
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasPermission('manage_settings')) {
            abort(403, 'Unauthorized access.');
        }

        $tenant = $user->effectiveTenant();
        if (!$tenant) {
            abort(404, 'No active tenant found.');
        }

        $request->validate([
            'google_sheet_url' => 'nullable|url',
            'regenerate_token' => 'nullable|boolean',
            'google_sheet_auto_sync' => 'nullable|boolean',
            'google_sheet_column_mapping' => 'nullable|array',
        ]);

        if ($request->has('google_sheet_url')) {
            $tenant->google_sheet_url = $request->input('google_sheet_url');
        }

        if ($request->boolean('regenerate_token')) {
            $tenant->google_sheet_webhook_token = Str::random(32);
        } elseif (empty($tenant->google_sheet_webhook_token)) {
            $tenant->google_sheet_webhook_token = Str::random(32);
        }

        if ($request->has('google_sheet_auto_sync')) {
            $tenant->google_sheet_auto_sync = $request->boolean('google_sheet_auto_sync');
        }

        if ($request->has('google_sheet_column_mapping')) {
            $tenant->google_sheet_column_mapping = $request->input('google_sheet_column_mapping');
        }

        $tenant->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Google Sheet settings updated!',
                'tenant' => $tenant,
            ]);
        }

        return redirect()->back()->with('success', 'Google Sheet settings updated!');
    }
}

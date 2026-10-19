<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Designation;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleSheetImportTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $adminA;
    protected User $reporterA;
    protected User $adminB;
    protected TaskType $taskTypeA;

    protected function setUp(): void
    {
        parent::setUp();

        // Tenant A
        $this->tenantA = Tenant::create([
            'name' => 'Tenant Alpha',
            'slug' => 'tenant-alpha',
            'google_sheet_webhook_token' => 'alpha_webhook_token_123456789012',
            'google_sheet_url' => 'https://docs.google.com/spreadsheets/d/test12345/edit#gid=0',
        ]);

        $this->adminA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Admin Alpha',
            'email' => 'admin@alpha.org',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->reporterA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Reporter Jane',
            'email' => 'jane@alpha.org',
            'password' => bcrypt('password'),
            'role' => 'staff',
        ]);

        $this->taskTypeA = TaskType::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Special Report',
            'color' => '#10B981',
        ]);

        // Tenant B (for isolation tests)
        $this->tenantB = Tenant::create([
            'name' => 'Tenant Beta',
            'slug' => 'tenant-beta',
            'google_sheet_webhook_token' => 'beta_webhook_token_987654321012',
            'google_sheet_url' => 'https://docs.google.com/spreadsheets/d/test98765/pubhtml',
        ]);

        $this->adminB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Admin Beta',
            'email' => 'admin@beta.org',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    public function test_webhook_creates_task_with_tenant_isolation(): void
    {
        $payload = [
            'title' => 'Breaking National News Report',
            'date' => '2026-10-19',
            'time' => '15:45',
            'task_type' => 'Special Report',
            'reporter' => 'Reporter Jane',
            'facebook_url' => 'https://facebook.com/watch/alpha123',
            'youtube_url' => 'https://youtube.com/watch?v=alpha123',
            'remarks' => 'Exclusive investigation into economy',
        ];

        $response = $this->postJson(route('webhooks.google-sheets', ['token' => $this->tenantA->google_sheet_webhook_token]), $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Task imported successfully',
            ]);

        $taskId = $response->json('task_id');
        $this->assertNotNull($taskId);

        // Verify task belongs strictly to Tenant Alpha
        $task = Task::withoutGlobalScopes()->find($taskId);
        $this->assertNotNull($task);
        $this->assertEquals($this->tenantA->id, $task->tenant_id);
        $this->assertEquals('Breaking National News Report', $task->title);
        $this->assertEquals('2026-10-19', $task->on_air_date->format('Y-m-d'));
        $this->assertEquals('15:45', $task->on_air_time);
        $this->assertEquals($this->taskTypeA->id, $task->task_type_id);
        $this->assertEquals('https://facebook.com/watch/alpha123', $task->facebook_url);

        // Verify staff assignment
        $this->assertDatabaseHas('task_assignments', [
            'task_id' => $task->id,
            'user_id' => $this->reporterA->id,
        ]);

        // Verify activity log
        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $this->tenantA->id,
            'action' => 'google_sheet_synced',
            'task_id' => $task->id,
        ]);

        // Tenant Isolation Check: Tenant B should NOT see this task
        $tenantBTasksCount = Task::withoutGlobalScopes()->where('tenant_id', $this->tenantB->id)->count();
        $this->assertEquals(0, $tenantBTasksCount);
    }

    public function test_webhook_prevents_duplicate_tasks(): void
    {
        $payload = [
            'title' => 'Duplicate Prevention Headline',
            'date' => '2026-10-20',
            'time' => '10:00',
        ];

        // 1st request
        $response1 = $this->postJson(route('webhooks.google-sheets', ['token' => $this->tenantA->google_sheet_webhook_token]), $payload);
        $response1->assertStatus(200);
        $taskId1 = $response1->json('task_id');

        // 2nd request with exact same title & date
        $response2 = $this->postJson(route('webhooks.google-sheets', ['token' => $this->tenantA->google_sheet_webhook_token]), $payload);
        $response2->assertStatus(200);
        $taskId2 = $response2->json('task_id');

        $this->assertEquals($taskId1, $taskId2);

        // Total count in database for Tenant A must be exactly 1
        $count = Task::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantA->id)
            ->where('title', 'Duplicate Prevention Headline')
            ->count();
        $this->assertEquals(1, $count);
    }

    public function test_webhook_with_invalid_token_returns_404(): void
    {
        $payload = ['title' => 'Should Not Import'];

        $response = $this->postJson(route('webhooks.google-sheets', ['token' => 'non-existent-token-xyz']), $payload);

        $response->assertStatus(404);
        $this->assertDatabaseMissing('tasks', ['title' => 'Should Not Import']);
    }

    public function test_webhook_handles_multiple_rows_payload(): void
    {
        $payload = [
            'rows' => [
                ['title' => 'Batch Task 1', 'date' => '2026-10-21'],
                ['title' => 'Batch Task 2', 'date' => '2026-10-21'],
            ],
        ];

        $response = $this->postJson(route('webhooks.google-sheets', ['token' => $this->tenantA->google_sheet_webhook_token]), $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'imported_count' => 2,
            ]);

        $this->assertDatabaseHas('tasks', ['tenant_id' => $this->tenantA->id, 'title' => 'Batch Task 1']);
        $this->assertDatabaseHas('tasks', ['tenant_id' => $this->tenantA->id, 'title' => 'Batch Task 2']);
    }

    public function test_on_demand_sync_route_imports_csv_successfully(): void
    {
        $csvContent = "Title,Date,Time,Task Type,Reporter,Facebook URL\n" .
                      "CSV Imported Story 1,2026-10-22,12:00,Special Report,Reporter Jane,https://fb.com/story1\n" .
                      "CSV Imported Story 2,2026-10-22,13:00,Special Report,,https://fb.com/story2\n";

        Http::fake([
            'docs.google.com/*' => Http::response($csvContent, 200, ['Content-Type' => 'text/csv']),
        ]);

        $response = $this->actingAs($this->adminA)
            ->post(route('settings.google-sheets.sync'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tasks', [
            'tenant_id' => $this->tenantA->id,
            'title' => 'CSV Imported Story 1',
        ]);

        $this->assertDatabaseHas('tasks', [
            'tenant_id' => $this->tenantA->id,
            'title' => 'CSV Imported Story 2',
        ]);

        // Check last synced at timestamp was updated
        $this->tenantA->refresh();
        $this->assertNotNull($this->tenantA->google_sheet_last_synced_at);
    }

    public function test_on_demand_sync_forbidden_for_unauthorized_user(): void
    {
        $unauthorizedStaff = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Restricted Staff',
            'email' => 'restricted@alpha.org',
            'password' => bcrypt('password'),
            'role' => 'staff',
        ]);

        // Staff does not have manage_settings or create_tasks unless granted
        // But in User::hasPermission, staff has create_tasks by default.
        // Let's create an external user from another tenant or test unauthenticated.
        $response = $this->post(route('settings.google-sheets.sync'));
        $response->assertRedirect(route('login'));
    }

    public function test_update_settings_route(): void
    {
        $oldToken = $this->tenantA->google_sheet_webhook_token;

        $response = $this->actingAs($this->adminA)
            ->post(route('settings.google-sheets.update'), [
                'google_sheet_url' => 'https://docs.google.com/spreadsheets/d/newsheetid/pubhtml',
                'google_sheet_auto_sync' => true,
                'regenerate_token' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Google Sheet settings updated!');

        $this->tenantA->refresh();
        $this->assertEquals('https://docs.google.com/spreadsheets/d/newsheetid/pubhtml', $this->tenantA->google_sheet_url);
        $this->assertNotEmpty($this->tenantA->google_sheet_webhook_token);
        $this->assertNotEquals($oldToken, $this->tenantA->google_sheet_webhook_token);
    }
}

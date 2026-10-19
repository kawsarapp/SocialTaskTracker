<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Task;
use App\Models\Designation;
use App\Models\TaskType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_toggle_designation_as_task_column()
    {
        $tenant = Tenant::create(['name' => 'Test Org', 'slug' => 'test-org']);
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@test.org',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $designation = Designation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Presenter',
            'color' => '#ffffff',
            'is_task_column' => false,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('settings.designations.toggle-column', $designation->id));

        $response->assertRedirect(route('settings.index'));
        $this->assertTrue($designation->fresh()->is_task_column);
    }

    public function test_can_create_task_with_dynamic_staff_assignments()
    {
        $tenant = Tenant::create(['name' => 'Test Org', 'slug' => 'test-org']);
        
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@test.org',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $scriptWriterRole = Designation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Script Writer',
            'color' => '#ffffff',
            'is_task_column' => true,
        ]);

        $staff = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Staff Writer',
            'email' => 'writer@test.org',
            'password' => bcrypt('password'),
            'role' => 'editor',
            'designation_id' => $scriptWriterRole->id,
        ]);

        $taskType = TaskType::create([
            'tenant_id' => $tenant->id,
            'name' => 'UNCUT',
            'color' => '#ffffff',
        ]);

        $taskData = [
            'title' => 'Sample Dynamic Task',
            'task_type_id' => $taskType->id,
            'on_air_time' => '10:00 AM',
            'on_air_date' => now()->format('Y-m-d'),
            'staff_assignments' => [
                $scriptWriterRole->id => $staff->id,
            ],
        ];

        $response = $this->actingAs($admin)
            ->post(route('tasks.store'), $taskData);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('tasks', [
            'title' => 'Sample Dynamic Task',
        ]);

        $task = Task::first();
        $this->assertNotNull($task);

        $this->assertDatabaseHas('task_assignments', [
            'task_id' => $task->id,
            'designation_id' => $scriptWriterRole->id,
            'user_id' => $staff->id,
        ]);

        // Test backward-compatible relation accessor
        $this->assertEquals($staff->id, $task->scriptWriter->id);
        $this->assertEquals('Staff Writer', $task->scriptWriter->name);
    }

    public function test_can_configure_task_form_fields_visibility_and_requirements()
    {
        $tenant = Tenant::create(['name' => 'Test Org', 'slug' => 'test-org']);
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@test.org',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $taskType = TaskType::create([
            'tenant_id' => $tenant->id,
            'name' => 'UNCUT',
            'color' => '#ffffff',
        ]);

        // Submit form field configuration update: youtube_url (required), shift_id (optional), and custom_field_location (required custom field!)
        $fieldsPayload = [
            'fields' => [
                'task_type_id' => ['visible' => '1', 'required' => '1'],
                'facebook_url' => ['visible' => '0', 'required' => '0'],
                'facebook_page_id' => ['visible' => '0', 'required' => '0'],
                'youtube_url' => ['visible' => '1', 'required' => '1'],
                'youtube_channel_id' => ['visible' => '0', 'required' => '0'],
                'on_air_time' => ['visible' => '1', 'required' => '1'],
                'on_air_date' => ['visible' => '1', 'required' => '1'],
                'area_id' => ['visible' => '0', 'required' => '0'],
                'team_id' => ['visible' => '0', 'required' => '0'],
                'shift_id' => ['visible' => '1', 'required' => '0'],
                'tags' => ['visible' => '0', 'required' => '0'],
                'remarks' => ['visible' => '0', 'required' => '0'],
                'thumbnail' => ['visible' => '0', 'required' => '0'],
                'script_file' => ['visible' => '0', 'required' => '0'],
                
                // Add a custom field
                'custom_field_location' => [
                    'visible' => '1',
                    'required' => '1',
                    'sort_order' => '15',
                    'is_custom' => '1',
                    'label' => 'Shooting Location',
                    'type' => 'text'
                ]
            ]
        ];

        $response = $this->actingAs($admin)
            ->post(route('settings.task-form-config.update'), $fieldsPayload);

        $response->assertRedirect(route('settings.index'));

        // Assert config values are correctly saved in tenant settings
        $config = $tenant->fresh()->task_form_config;
        $this->assertTrue($config['youtube_url']['required']);
        $this->assertTrue($config['custom_field_location']['is_custom']);
        $this->assertEquals('Shooting Location', $config['custom_field_location']['label']);

        // Assert saving task fails if required custom field (custom_fields.custom_field_location) is missing
        $invalidTask = [
            'title' => 'Missing Location Task',
            'task_type_id' => $taskType->id,
            'youtube_url' => 'https://youtube.com/watch?v=123',
            'on_air_time' => '10:00 AM',
            'on_air_date' => now()->format('Y-m-d'),
        ];
        $response = $this->actingAs($admin)
            ->post(route('tasks.store'), $invalidTask);

        $response->assertSessionHasErrors(['custom_fields.custom_field_location']);

        // Assert saving task succeeds if all required standard & custom fields are supplied
        $validTask = [
            'title' => 'Valid Location Task',
            'task_type_id' => $taskType->id,
            'youtube_url' => 'https://youtube.com/watch?v=123',
            'on_air_time' => '10:00 AM',
            'on_air_date' => now()->format('Y-m-d'),
            'custom_fields' => [
                'custom_field_location' => 'Dhaka Shooting Studio'
            ]
        ];
        $response = $this->actingAs($admin)
            ->post(route('tasks.store'), $validTask);

        $response->assertRedirect(route('dashboard'));

        // Assert task and custom field value exist in the database
        $this->assertDatabaseHas('tasks', [
            'title' => 'Valid Location Task',
        ]);

        $task = Task::where('title', 'Valid Location Task')->first();
        $this->assertNotNull($task->custom_fields);
        $this->assertEquals('Dhaka Shooting Studio', $task->custom_fields['custom_field_location']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\FacebookPage;
use App\Models\SocialPost;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\Tenant;
use App\Models\User;
use App\Models\YoutubeChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SocialPublishTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $teamLeader;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'News Network',
            'slug' => 'news-net',
            'facebook_page_access_token' => 'mock_fb_token_123',
            'youtube_api_key' => 'mock_yt_key_123',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Boss',
            'email' => 'admin@news.org',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        $this->teamLeader = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Lead Producer',
            'email' => 'lead@news.org',
            'password' => bcrypt('secret'),
            'role' => 'team_leader',
        ]);

        $this->staff = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Staff Member',
            'email' => 'staff@news.org',
            'password' => bcrypt('secret'),
            'role' => 'staff',
        ]);
    }

    public function test_staff_without_permission_is_forbidden_from_publishing()
    {
        $restrictedDesignation = \App\Models\Designation::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Restricted Staff',
            'permissions' => ['view_tasks'],
        ]);
        $this->staff->update(['designation_id' => $restrictedDesignation->id]);

        $response = $this->actingAs($this->staff)->post(route('social-publish.store'), [
            'title' => 'Breaking Report',
            'post_type' => 'video',
            'platforms' => ['facebook'],
        ]);

        $response->assertStatus(403);
    }

    public function test_team_leader_has_permission_and_can_publish_post()
    {
        Storage::fake('public');

        $fbPage = FacebookPage::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Main News Page',
            'is_active' => true,
        ]);

        $ytChannel = YoutubeChannel::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Official Channel',
            'is_active' => true,
        ]);

        $taskType = TaskType::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Daily News',
            'color' => '#3b82f6',
        ]);

        $task = Task::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Evening Bulletin',
            'task_type_id' => $taskType->id,
            'entry_by' => $this->teamLeader->id,
            'on_air_date' => now()->toDateString(),
            'on_air_time' => '20:00',
        ]);

        $media = UploadedFile::fake()->create('bulletin.mp4', 5000, 'video/mp4');
        $thumbnail = UploadedFile::fake()->image('thumb.jpg', 640, 480);

        $response = $this->actingAs($this->teamLeader)->post(route('social-publish.store'), [
            'title' => 'Evening Bulletin Broadcast',
            'content' => 'Highlights from today broadcast.',
            'post_type' => 'video',
            'platforms' => ['facebook', 'youtube'],
            'facebook_page_id' => $fbPage->id,
            'youtube_channel_id' => $ytChannel->id,
            'task_id' => $task->id,
            'media' => $media,
            'thumbnail' => $thumbnail,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check SocialPost record
        $this->assertDatabaseHas('social_posts', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->teamLeader->id,
            'task_id' => $task->id,
            'title' => 'Evening Bulletin Broadcast',
            'post_type' => 'video',
            'status' => 'published',
        ]);

        $socialPost = SocialPost::where('task_id', $task->id)->first();
        $this->assertNotNull($socialPost);
        $this->assertContains('facebook', $socialPost->platforms);
        $this->assertContains('youtube', $socialPost->platforms);
        $this->assertNotNull($socialPost->platform_results['facebook']['url']);
        $this->assertNotNull($socialPost->platform_results['youtube']['url']);

        // Check Task links were updated
        $task->refresh();
        $this->assertEquals($socialPost->platform_results['facebook']['url'], $task->facebook_url);
        $this->assertEquals($socialPost->platform_results['youtube']['url'], $task->youtube_url);
        $this->assertEquals($fbPage->id, $task->facebook_page_id);
        $this->assertEquals($ytChannel->id, $task->youtube_channel_id);

        // Check Activity Log
        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $this->tenant->id,
            'action' => 'social_published',
            'task_id' => $task->id,
        ]);
    }

    public function test_can_view_social_publish_history()
    {
        SocialPost::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'title' => 'Morning Headline',
            'content' => 'Brief morning overview',
            'post_type' => 'reel',
            'platforms' => ['facebook'],
            'status' => 'published',
            'published_at' => now(),
            'platform_results' => [
                'facebook' => ['url' => 'https://facebook.com/reel/123456789', 'success' => true]
            ],
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('social-publish.history'));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'post_type', 'platforms', 'platform_results']
            ]
        ]);
    }
}

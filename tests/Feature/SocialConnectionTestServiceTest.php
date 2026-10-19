<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\SocialConnectionTestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SocialConnectionTestServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $staff;
    protected SocialConnectionTestService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Workspace',
            'slug' => 'test-workspace',
            'facebook_page_access_token' => 'saved_fb_token_xyz',
            'youtube_api_key' => 'saved_yt_key_xyz',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        $this->staff = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Staff Member',
            'email' => 'staff@test.com',
            'password' => bcrypt('secret123'),
            'role' => 'staff',
        ]);

        $this->service = new SocialConnectionTestService();
    }

    /**
     * Facebook Token Tests
     */
    public function test_facebook_test_returns_error_when_no_token_provided_or_configured(): void
    {
        $tenantNoToken = Tenant::create(['name' => 'Empty Token Tenant', 'slug' => 'empty-token']);
        $result = $this->service->testFacebookToken(null, $tenantNoToken);

        $this->assertFalse($result['success']);
        $this->assertEquals('missing_token', $result['status']);
        $this->assertStringContainsString('No Facebook Page Access Token', $result['error']);
    }

    public function test_facebook_test_succeeds_with_healthy_permissions(): void
    {
        Http::fake([
            'https://graph.facebook.com/v19.0/me?*' => Http::response([
                'id' => '1234567890',
                'name' => 'Awesome Media Page',
                'category' => 'News & media website',
                'link' => 'https://facebook.com/1234567890',
                'is_published' => true,
            ], 200),
            'https://graph.facebook.com/v19.0/me/permissions?*' => Http::response([
                'data' => [
                    ['permission' => 'pages_show_list', 'status' => 'granted'],
                    ['permission' => 'pages_read_engagement', 'status' => 'granted'],
                    ['permission' => 'pages_manage_posts', 'status' => 'granted'],
                    ['permission' => 'publish_video', 'status' => 'granted'],
                    ['permission' => 'read_insights', 'status' => 'granted'],
                ]
            ], 200),
        ]);

        $result = $this->service->testFacebookToken('valid_fb_token', $this->tenant);

        $this->assertTrue($result['success']);
        $this->assertEquals('healthy', $result['status']);
        $this->assertEquals('Awesome Media Page', $result['account_name']);
        $this->assertEquals('1234567890', $result['account_id']);
        $this->assertTrue($result['capabilities']['can_publish_posts']);
        $this->assertTrue($result['capabilities']['can_publish_reels']);
        $this->assertTrue($result['capabilities']['can_sync_analytics']);
        $this->assertEmpty($result['missing_permissions']);
    }

    public function test_facebook_test_detects_missing_publishing_permissions(): void
    {
        Http::fake([
            'https://graph.facebook.com/v19.0/me?*' => Http::response([
                'id' => '1234567890',
                'name' => 'Read Only Page',
                'category' => 'Media',
                'is_published' => true,
            ], 200),
            'https://graph.facebook.com/v19.0/me/permissions?*' => Http::response([
                'data' => [
                    ['permission' => 'pages_show_list', 'status' => 'granted'],
                    ['permission' => 'pages_read_engagement', 'status' => 'granted'],
                ]
            ], 200),
        ]);

        $result = $this->service->testFacebookToken('read_only_token', $this->tenant);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['capabilities']['can_publish_posts']);
        $this->assertFalse($result['capabilities']['can_publish_reels']);
        $this->assertTrue($result['capabilities']['can_sync_analytics']);
        $this->assertArrayHasKey('pages_manage_posts', $result['missing_permissions']);
        $this->assertArrayHasKey('publish_video', $result['missing_permissions']);
        $this->assertStringContainsString('pages_manage_posts', $result['missing_permissions']['pages_manage_posts']['advice']);
    }

    public function test_facebook_test_diagnoses_expired_token(): void
    {
        Http::fake([
            'https://graph.facebook.com/v19.0/me?*' => Http::response([
                'error' => [
                    'message' => 'Error validating access token: Session has expired on ...',
                    'type' => 'OAuthException',
                    'code' => 190,
                    'error_subcode' => 463,
                ]
            ], 400),
        ]);

        $result = $this->service->testFacebookToken('expired_token', $this->tenant);

        $this->assertFalse($result['success']);
        $this->assertEquals(190, $result['error_code']);
        $this->assertEquals(463, $result['error_subcode']);
        $this->assertStringContainsString('Token Expired', $result['error']);
        $this->assertStringContainsString('never-expiring Page Access Token', $result['troubleshooting']);
    }

    public function test_facebook_test_diagnoses_invalid_token(): void
    {
        Http::fake([
            'https://graph.facebook.com/v19.0/me?*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token signature.',
                    'type' => 'OAuthException',
                    'code' => 190,
                    'error_subcode' => 467,
                ]
            ], 400),
        ]);

        $result = $this->service->testFacebookToken('corrupt_token', $this->tenant);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid Access Token', $result['error']);
    }

    public function test_facebook_test_detects_user_access_token_and_returns_managed_pages_and_guidance(): void
    {
        Http::fake([
            'https://graph.facebook.com/v19.0/me?fields=id%2Cname*' => Http::response([
                'id' => 'user_12345',
                'name' => 'Kawsar Ahmed',
            ], 200),
            'https://graph.facebook.com/v19.0/me/accounts*' => Http::response([
                'data' => [
                    [
                        'id' => 'page_98765',
                        'name' => 'Kawsar Tech Media',
                        'category' => 'Technology',
                        'tasks' => ['MANAGE', 'CREATE_CONTENT'],
                    ],
                    [
                        'id' => 'page_54321',
                        'name' => 'Bangladesh News Hub',
                        'category' => 'News & Media',
                        'tasks' => ['MANAGE'],
                    ]
                ]
            ], 200),
            'https://graph.facebook.com/v19.0/me/permissions*' => Http::response([
                'data' => [
                    ['permission' => 'pages_show_list', 'status' => 'granted'],
                    ['permission' => 'pages_read_engagement', 'status' => 'granted'],
                    ['permission' => 'pages_manage_posts', 'status' => 'granted'],
                ]
            ], 200),
            'https://graph.facebook.com/v19.0/me?fields=category%2Clink%2Cis_published*' => Http::response([
                'error' => [
                    'message' => '(#100) Tried accessing nonexisting field (category) on node type (User)',
                    'type' => 'OAuthException',
                    'code' => 100,
                ]
            ], 400),
        ]);

        $result = $this->service->testFacebookToken('user_token_abc', $this->tenant);

        $this->assertTrue($result['success']);
        $this->assertEquals('user_token', $result['token_type']);
        $this->assertTrue($result['is_user_token']);
        $this->assertEquals('restricted', $result['status']);
        $this->assertCount(2, $result['pages']);
        $this->assertEquals('Kawsar Tech Media', $result['pages'][0]['name']);
        $this->assertEquals('page_98765', $result['pages'][0]['id']);
        $this->assertNotNull($result['token_guidance']);
        $this->assertStringContainsString('User Access Token', $result['token_guidance']['explanation_bn']);
        $this->assertStringContainsString('Meta Graph API Explorer', $result['token_guidance']['steps_bn'][0]);
        // User tokens cannot directly publish
        $this->assertFalse($result['capabilities']['can_publish_posts']);
        $this->assertFalse($result['capabilities']['can_publish_reels']);
    }

    public function test_facebook_test_diagnoses_nonexisting_field_error_with_user_token_guidance(): void
    {
        Http::fake([
            'https://graph.facebook.com/v19.0/me?*' => Http::response([
                'error' => [
                    'message' => '(#100) Tried accessing nonexisting field (category) on node type (User)',
                    'type' => 'OAuthException',
                    'code' => 100,
                ]
            ], 400),
        ]);

        $result = $this->service->testFacebookToken('user_token_category_fail', $this->tenant);

        $this->assertFalse($result['success']);
        $this->assertEquals(100, $result['error_code']);
        $this->assertEquals('User Access Token Detected (Page Token Required)', $result['error']);
        $this->assertStringContainsString('User Access Token', $result['troubleshooting']);
        $this->assertStringContainsString('Meta Graph API Explorer', $result['troubleshooting']);
        $this->assertStringContainsString('Page Access Token', $result['troubleshooting']);
    }

    /**
     * YouTube API Key Tests
     */
    public function test_youtube_test_returns_error_when_no_key_provided_or_configured(): void
    {
        $tenantNoKey = Tenant::create(['name' => 'No YT Key', 'slug' => 'no-yt-key']);
        $result = $this->service->testYoutubeApiKey(null, $tenantNoKey);

        $this->assertFalse($result['success']);
        $this->assertEquals('missing_key', $result['status']);
        $this->assertStringContainsString('No YouTube API Key', $result['error']);
    }

    public function test_youtube_test_succeeds_with_standard_query(): void
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos?*' => Http::response([
                'kind' => 'youtube#videoListResponse',
                'items' => [
                    ['id' => 'abc12345', 'snippet' => ['title' => 'Sample Video']]
                ]
            ], 200),
        ]);

        $result = $this->service->testYoutubeApiKey('valid_yt_key', $this->tenant);

        $this->assertTrue($result['success']);
        $this->assertEquals('healthy', $result['status']);
        $this->assertTrue($result['capabilities']['can_read_metrics']);
        $this->assertStringContainsString('operational', $result['summary_message']);
    }

    public function test_youtube_test_diagnoses_invalid_key(): void
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos?*' => Http::response([
                'error' => [
                    'code' => 400,
                    'message' => 'API key not valid. Please pass a valid API key.',
                    'errors' => [
                        ['reason' => 'keyInvalid', 'message' => 'API key not valid.']
                    ]
                ]
            ], 400),
        ]);

        $result = $this->service->testYoutubeApiKey('invalid_key_string', $this->tenant);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid YouTube API Key', $result['error']);
        $this->assertStringContainsString('Google Cloud Console', $result['troubleshooting']);
    }

    public function test_youtube_test_diagnoses_disabled_service(): void
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos?*' => Http::response([
                'error' => [
                    'code' => 403,
                    'message' => 'YouTube Data API v3 has not been used in project ... or it is disabled.',
                    'errors' => [
                        ['reason' => 'accessNotConfigured', 'message' => 'API not enabled.']
                    ]
                ]
            ], 403),
        ]);

        $result = $this->service->testYoutubeApiKey('disabled_api_key', $this->tenant);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('YouTube Data API v3 is Disabled', $result['error']);
        $this->assertStringContainsString('Enable', $result['troubleshooting']);
    }

    public function test_youtube_test_diagnoses_quota_exceeded(): void
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos?*' => Http::response([
                'error' => [
                    'code' => 403,
                    'message' => 'The request cannot be completed because you have exceeded your quota.',
                    'errors' => [
                        ['reason' => 'quotaExceeded', 'message' => 'Quota exceeded.']
                    ]
                ]
            ], 403),
        ]);

        $result = $this->service->testYoutubeApiKey('quota_exceeded_key', $this->tenant);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Daily Quota Exceeded', $result['error']);
        $this->assertStringContainsString('10,000 units/day', $result['troubleshooting']);
    }

    /**
     * HTTP Controller Endpoints Tests
     */
    public function test_ajax_facebook_test_endpoint_as_admin(): void
    {
        Http::fake([
            'https://graph.facebook.com/v19.0/me?*' => Http::response([
                'id' => '99887766',
                'name' => 'Ajax Tested Page',
                'category' => 'News',
            ], 200),
            'https://graph.facebook.com/v19.0/me/permissions?*' => Http::response([
                'data' => [
                    ['permission' => 'pages_show_list', 'status' => 'granted'],
                    ['permission' => 'pages_manage_posts', 'status' => 'granted'],
                ]
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('settings.api-keys.test-facebook'), [
                'token' => 'live_token_from_input',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'platform' => 'facebook',
            'account_name' => 'Ajax Tested Page',
        ]);
    }

    public function test_ajax_youtube_test_endpoint_as_admin(): void
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos?*' => Http::response([
                'items' => [['id' => '123']]
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('settings.api-keys.test-youtube'), [
                'api_key' => 'live_yt_key_from_input',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'platform' => 'youtube',
            'status' => 'healthy',
        ]);
    }

    public function test_unauthorized_staff_cannot_run_social_tests(): void
    {
        $response = $this->actingAs($this->staff)
            ->postJson(route('settings.api-keys.test-facebook'));

        $response->assertStatus(403);
    }

    public function test_settings_index_page_renders_test_buttons_and_modal(): void
    {
        $response = $this->actingAs($this->admin)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Test Facebook Connection');
        $response->assertSee('Test YouTube Connection');
        $response->assertSee('Test YouTube API Key');
        $response->assertSee('Test Facebook Token &amp; Permissions', false);
        $response->assertSee('socialTestModalOpen');
        $response->assertSee('runSocialTest');
        $response->assertSee('User Access Token Detected (Page Token Required for Publishing)');
        $response->assertSee('developers.facebook.com/tools/explorer');
    }
}

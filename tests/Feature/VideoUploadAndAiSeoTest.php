<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\VideoUpload;
use App\Services\CloudflareR2Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VideoUploadAndAiSeoTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $adminUser;
    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'      => 'Test Channel Media',
            'slug'      => 'test-media',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin User',
            'email'     => 'admin@testmedia.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
        ]);

        $this->staffUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Staff User',
            'email'     => 'staff@testmedia.com',
            'password'  => bcrypt('password'),
            'role'      => 'staff',
        ]);
    }

    public function test_initiate_multipart_upload_creates_record_and_returns_upload_id(): void
    {
        $mockR2 = $this->createMock(CloudflareR2Service::class);
        $mockR2->expects($this->once())
            ->method('generatePresignedMultipartUpload')
            ->willReturn(['upload_id' => 'mock-upload-id-1234', 'key' => 'videos/1/test.mp4']);
        $this->app->instance(CloudflareR2Service::class, $mockR2);

        $response = $this->actingAs($this->adminUser)->postJson(route('video-uploads.initiate'), [
            'filename'     => 'breaking-news.mp4',
            'content_type' => 'video/mp4',
            'file_size'    => 15728640, // 15MB
        ]);

        $response->assertOk()
            ->assertJson([
                'success'   => true,
                'upload_id' => 'mock-upload-id-1234',
            ]);

        $this->assertDatabaseHas('video_uploads', [
            'tenant_id'         => $this->tenant->id,
            'original_filename' => 'breaking-news.mp4',
            'status'            => 'uploading',
        ]);
    }

    public function test_get_part_upload_url_returns_presigned_url(): void
    {
        $mockR2 = $this->createMock(CloudflareR2Service::class);
        $mockR2->expects($this->once())
            ->method('presignPartUpload')
            ->with('videos/1/test.mp4', 'upload-id-xyz', 1)
            ->willReturn('https://mock-r2.cloudflarestorage.com/part1?signature=abc');
        $this->app->instance(CloudflareR2Service::class, $mockR2);

        $response = $this->actingAs($this->adminUser)->postJson(route('video-uploads.part-url'), [
            'key'         => 'videos/1/test.mp4',
            'upload_id'   => 'upload-id-xyz',
            'part_number' => 1,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'url'     => 'https://mock-r2.cloudflarestorage.com/part1?signature=abc',
            ]);
    }

    public function test_complete_upload_finalizes_r2_and_updates_status(): void
    {
        $videoUpload = VideoUpload::create([
            'tenant_id'         => $this->tenant->id,
            'user_id'           => $this->adminUser->id,
            'original_filename' => 'metro-rail.mp4',
            'r2_key'            => 'videos/1/metro-rail.mp4',
            'status'            => 'uploading',
        ]);

        $mockR2 = $this->createMock(CloudflareR2Service::class);
        $mockR2->expects($this->once())
            ->method('completeMultipartUpload')
            ->willReturn([
                'url' => 'https://pub-mock.r2.dev/videos/1/metro-rail.mp4',
                'key' => 'videos/1/metro-rail.mp4',
            ]);
        $this->app->instance(CloudflareR2Service::class, $mockR2);

        // Mock Gemini API call
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'seo_title'   => 'মেট্রোরেল নতুন সময়সূচী ঘোষণা | Metro Rail New Schedule',
                                        'description' => 'মেট্রোরেলের নতুন সময়সূচী সংক্রান্ত বিস্তারিত খবর।',
                                        'tags'        => ['মেট্রোরেল', 'metro rail', 'dhaka metro'],
                                        'hashtags'    => ['#MetroRail', '#DhakaMetro', '#BreakingNews'],
                                        'chapters'    => [['time' => '0:00', 'title' => 'Introduction']],
                                        'keywords'    => ['metro', 'rail', 'dhaka'],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->adminUser)->postJson(route('video-uploads.complete'), [
            'key'             => 'videos/1/metro-rail.mp4',
            'upload_id'       => 'mock-upload-id',
            'parts'           => [['PartNumber' => 1, 'ETag' => 'etag1']],
            'video_upload_id' => $videoUpload->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'r2_url'  => 'https://pub-mock.r2.dev/videos/1/metro-rail.mp4',
            ]);

        $this->assertDatabaseHas('video_uploads', [
            'id'     => $videoUpload->id,
            'status' => 'ready',
            'r2_url' => 'https://pub-mock.r2.dev/videos/1/metro-rail.mp4',
        ]);
    }

    public function test_ai_seo_generation_returns_tags_and_hashtags(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'seo_title'   => 'পদ্মা সেতুতে বিশ্ব রেকর্ড | Padma Bridge Viral Update',
                                        'description' => 'পদ্মা সেতুর নতুন উন্নয়ন সম্পর্কিত বিস্তারিত তথ্য।',
                                        'tags'        => ['পদ্মা সেতু', 'padma bridge', 'bangladesh news', 'mega project'],
                                        'hashtags'    => ['#PadmaBridge', '#Bangladesh', '#MegaProject', '#ViralNews'],
                                        'chapters'    => [
                                            ['time' => '0:00', 'title' => 'Overview'],
                                            ['time' => '1:00', 'title' => 'Project Details'],
                                        ],
                                        'keywords'    => ['padma', 'bridge', 'mega'],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->adminUser)->postJson(route('video-uploads.ai-seo'), [
            'title' => 'পদ্মা সেতুর নতুন আপডেট',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'seo'     => [
                    'seo_title' => 'পদ্মা সেতুতে বিশ্ব রেকর্ড | Padma Bridge Viral Update',
                ],
            ]);

        $data = $response->json('seo');
        $this->assertCount(4, $data['tags']);
        $this->assertCount(4, $data['hashtags']);
        $this->assertEquals('#PadmaBridge', $data['hashtags'][0]);
    }

    public function test_suggest_thumbnail_text_endpoint(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'main_text'      => 'বড় সুখবর!',
                                        'sub_text'       => 'মেট্রোরেলে নতুন সুবিধা যুক্ত হলো',
                                        'call_to_action' => 'বিস্তারিত দেখুন',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->adminUser)->postJson(route('video-uploads.thumbnail-text'), [
            'title' => 'মেট্রোরেলে বড় ঘোষণা',
        ]);

        $response->assertOk()
            ->assertJson([
                'success'     => true,
                'suggestions' => [
                    'main_text' => 'বড় সুখবর!',
                ],
            ]);
    }

    public function test_youtube_oauth_redirect_generates_auth_url(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('youtube.oauth'));

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com/o/oauth2/v2/auth', $response->headers->get('Location'));
    }
}

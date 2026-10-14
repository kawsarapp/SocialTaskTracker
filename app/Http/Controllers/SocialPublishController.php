<?php

namespace App\Http\Controllers;

use App\Models\SocialPost;
use App\Services\SocialPublishService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SocialPublishController extends Controller
{
    public function __construct(
        protected SocialPublishService $socialPublishService
    ) {}

    /**
     * Store and publish a new social media post across selected platforms.
     */
    public function store(Request $request)
    {
        abort_unless(Auth::user()->hasPermission('publish_social_posts'), 403, 'Unauthorized access to social media publishing.');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'post_type' => 'required|in:video,reel,short,post',
            'media' => 'nullable|file|max:102400',
            'thumbnail' => 'nullable|image|max:10240',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'in:facebook,youtube,instagram',
            'facebook_page_id' => 'nullable|exists:facebook_pages,id',
            'youtube_channel_id' => 'nullable|exists:youtube_channels,id',
            'task_id' => 'nullable|exists:tasks,id',
        ]);

        $user = Auth::user();
        $post = new SocialPost([
            'tenant_id' => $user->effectiveTenantId(),
            'user_id' => $user->id,
            'task_id' => $validated['task_id'] ?? null,
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'post_type' => $validated['post_type'],
            'platforms' => $validated['platforms'],
            'facebook_page_id' => $validated['facebook_page_id'] ?? null,
            'youtube_channel_id' => $validated['youtube_channel_id'] ?? null,
        ]);

        $mediaFile = $request->file('media');
        $thumbnailFile = $request->file('thumbnail');

        $publishedPost = $this->socialPublishService->publishPost($post, $mediaFile, $thumbnailFile);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Social media post published successfully!',
                'post' => $publishedPost->load(['user', 'task', 'facebookPage', 'youtubeChannel']),
            ]);
        }

        $platformNames = implode(', ', array_map('ucfirst', $publishedPost->platforms ?? []));
        return redirect()->back()->with('success', "Post '{$publishedPost->title}' published successfully to {$platformNames}!");
    }

    /**
     * Retrieve published social post history.
     */
    public function history(Request $request)
    {
        abort_unless(Auth::user()->hasPermission('publish_social_posts') || Auth::user()->hasPermission('view_tasks'), 403);

        $posts = SocialPost::with(['user', 'task', 'facebookPage', 'youtubeChannel'])
            ->latest()
            ->paginate(20);

        if ($request->wantsJson()) {
            return response()->json($posts);
        }

        if (view()->exists('social.history')) {
            return view('social.history', compact('posts'));
        }

        return response()->json($posts);
    }
}

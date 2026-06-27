<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Store a newly created comment in storage.
     */
    public function store(Request $request, Task $task)
    {
        // Enforce tenant boundary safety
        if ($task->tenant_id !== Auth::user()->tenant_id && !Auth::user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = Comment::create([
            'tenant_id' => Auth::user()->tenant_id,
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'content' => $request->content,
        ]);

        // Load relations for payload
        $comment->load(['user', 'task']);

        // Dispatch Reverb event
        event(new \App\Events\CommentPosted($comment));

        return redirect()->back()->with('success', 'Comment posted successfully!');
    }

    /**
     * Poll new comments in the tenant scoped workspace.
     */
    public function poll(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $lastCommentId = $request->input('last_id', 0);

        // Fetch comments with ID > lastCommentId within tenant scope
        $newComments = Comment::with(['user', 'task'])
            ->where('tenant_id', $tenantId)
            ->where('id', '>', $lastCommentId)
            ->orderBy('id', 'asc')
            ->get();

        $formatted = $newComments->map(function ($comment) {
            return [
                'id' => $comment->id,
                'task_id' => $comment->task_id,
                'tenant_id' => $comment->tenant_id,
                'user_name' => $comment->user->name ?? 'System',
                'user_role' => $comment->user->role ?? 'staff',
                'content' => $comment->content,
                'task_title' => $comment->task->title ?? 'Task Details',
                'created_at_human' => $comment->created_at->diffForHumans(),
                'edit_url' => route('tasks.edit', $comment->task_id),
            ];
        });

        return response()->json($formatted);
    }
}

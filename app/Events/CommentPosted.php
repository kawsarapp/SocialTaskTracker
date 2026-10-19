<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentPosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $commentData;

    /**
     * Create a new event instance.
     */
    public function __construct(Comment $comment)
    {
        $this->commentData = [
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
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->commentData['tenant_id']),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'comment.posted';
    }
}

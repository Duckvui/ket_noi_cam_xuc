<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BinhLuanDaTao implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $postId,
        public readonly array $comment,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("post.{$this->postId}")];
    }

    public function broadcastAs(): string
    {
        return 'comment.created';
    }

    public function broadcastWith(): array
    {
        return ['post_id' => $this->postId, 'comment' => $this->comment];
    }
}

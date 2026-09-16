<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class TinNhanDaGui implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $conversationId, public array $memberIds) {}

    public function broadcastOn(): array
    {
        return array_map(fn ($id) => new PrivateChannel('chat-user.'.$id), $this->memberIds);
    }

    public function broadcastAs(): string { return 'tin-nhan.da-gui'; }

    // No private message payload: clients fetch it through the authorized API.
    public function broadcastWith(): array { return ['conversation_id' => $this->conversationId]; }
}

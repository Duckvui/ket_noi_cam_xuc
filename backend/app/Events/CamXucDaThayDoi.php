<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class CamXucDaThayDoi implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $idTaiKhoan) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('cam-xuc.'.$this->idTaiKhoan)];
    }

    public function broadcastAs(): string
    {
        return 'cam-xuc.thay-doi';
    }

    public function broadcastWith(): array
    {
        // Invalidation only. API rechecks privacy, even for already subscribed clients.
        return ['idTaiKhoan' => $this->idTaiKhoan];
    }
}

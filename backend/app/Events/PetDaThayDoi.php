<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class PetDaThayDoi implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $idPet) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('pet.'.$this->idPet)];
    }

    public function broadcastAs(): string
    {
        return 'pet.thay-doi';
    }

    public function broadcastWith(): array
    {
        return ['idPet' => $this->idPet];
    }
}

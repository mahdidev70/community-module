<?php

namespace TechStudio\Community\app\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemoveChatroomMember implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $roomId;
    public $removeMember;
    public function __construct($roomId,$removeMember)
    {
        $this->roomId = $roomId;
        $this->removeMember = $removeMember;
    }

    public function broadcastOn()
    {
        return new Channel('chat.' . $this->roomId);
    }
}

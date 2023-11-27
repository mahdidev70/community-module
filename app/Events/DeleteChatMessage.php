<?php

namespace TechStudio\Community\app\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeleteChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $deleteMessage;
    /**
     * Create a new event instance.
     */
    public function __construct($roomId,$deleteMessage)
    {
        $this->roomId = $roomId;
        $this->deleteMessage = $deleteMessage;
    }

    public function broadcastOn()
    {
        return new Channel('chat.' . $this->roomId);
    }
}

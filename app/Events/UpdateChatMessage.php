<?php

namespace TechStudio\Community\app\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $updateMessage;
    /**
     * Create a new event instance.
     */

     public function __construct($roomId,$updateMessage)
     {
         $this->roomId = $roomId;
         $this->updateMessage = $updateMessage;
     }

    public function broadcastOn()
    {
        return new Channel('chat.' . $this->roomId);
    }
}

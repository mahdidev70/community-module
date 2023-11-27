<?php

namespace TechStudio\Community\app\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $lastMessageId;
    public $newMessage;

    public function __construct($roomId, $lastMessageId, $newMessage)
    {
        $this->roomId = $roomId;
        $this->lastMessageId = $lastMessageId;
        $this->newMessage = $newMessage;
    }

    public function broadcastOn()
    {
        return new Channel('chat.' . $this->roomId);
    }
}

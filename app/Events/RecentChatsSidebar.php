<?php

namespace TechStudio\Community\app\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RecentChatsSidebar implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $recentChatsSidebar;
    /**
     * Create a new event instance.
     */
    public function __construct($userId,$recentChatsSidebar)
    {
        $this->userId = $userId;
        $this->recentChatsSidebar = $recentChatsSidebar;
    }

    public function broadcastOn()
    {
        return new Channel('user.' . $this->userId);
    }
}

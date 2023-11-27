<?php

namespace TechStudio\Community\app\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AddChatroomMember implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $member;
    public $memberCount;
    public function __construct($roomId,$member,$memberCount)
    {
        $this->roomId = $roomId;
        $this->member = $member;
        $this->memberCount = $memberCount;
    }


    public function broadcastOn()
    {
        return new Channel('chat.' . $this->roomId);
    }
}

<?php

namespace TechStudio\Community\app\Listeners;

use App\Events\CoverChatRoom;
use App\Events\DeleteChatMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendCoverChatroomToClients
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handle(CoverChatRoom $event)
    {
        logger($event->chatRoom);
    }
}

<?php

namespace TechStudio\Community\app\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use TechStudio\Community\app\Events\CoverChatRoom;

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

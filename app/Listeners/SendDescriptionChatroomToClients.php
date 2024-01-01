<?php

namespace TechStudio\Community\app\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use TechStudio\Community\app\Events\EditDescriptionChatroom;

class SendDescriptionChatroomToClients
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handle(EditDescriptionChatroom $event)
    {
        logger($event->chatRoom);
    }
}

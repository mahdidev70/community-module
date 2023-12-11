<?php

namespace TechStudio\Community\app\Listeners;

use App\Events\CoverChatRoom;
use App\Events\EditDescriptionChatroom;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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

<?php

namespace TechStudio\Community\app\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use TechStudio\Community\app\Events\DeleteChatMessage;

class SendDeleteChatMessageToClients
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handle(DeleteChatMessage $event)
    {
        //logger($event->deleteMessage);
    }
}

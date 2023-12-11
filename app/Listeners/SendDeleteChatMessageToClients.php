<?php

namespace TechStudio\Community\app\Listeners;

use App\Events\DeleteChatMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
        logger($event->deleteMessage);
    }
}

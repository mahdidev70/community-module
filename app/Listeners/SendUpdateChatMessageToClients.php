<?php

namespace TechStudio\Community\app\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use TechStudio\Community\app\Events\UpdateChatMessage;

class SendUpdateChatMessageToClients
{
    public function __construct()
    {
        //
    }

    public function handle(UpdateChatMessage $event)
    {
        logger($event->updateMessage);
    }
}

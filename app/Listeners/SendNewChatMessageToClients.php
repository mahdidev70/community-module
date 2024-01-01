<?php

namespace TechStudio\Community\app\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use TechStudio\Community\app\Events\NewChatMessage;

class SendNewChatMessageToClients
{
    public function __construct()
    {
    }

    public function handle(NewChatMessage $event)
    {
        logger($event->newMessage['message']);
    }
}

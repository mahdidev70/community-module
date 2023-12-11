<?php

namespace TechStudio\Community\app\Listeners;

use App\Events\NewChatMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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

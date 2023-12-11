<?php

namespace TechStudio\Community\app\Listeners;

use App\Events\UpdateChatMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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

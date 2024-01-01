<?php

namespace TechStudio\Community\app\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use TechStudio\Community\app\Events\UnreadCountMember;

class SendUnreadCountMember
{
    public function handle(UnreadCountMember $event)
    {
        logger([$event->roomId,$event->userId]);
    }
}

<?php

namespace TechStudio\Community\app\Listeners;

use App\Events\UnreadCountMember;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendUnreadCountMember
{
    public function handle(UnreadCountMember $event)
    {
        logger([$event->roomId,$event->userId]);
    }
}

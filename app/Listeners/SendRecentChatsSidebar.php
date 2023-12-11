<?php

namespace TechStudio\Community\app\Listeners;

use App\Events\RecentChatsSidebar;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendRecentChatsSidebar
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
        public function handle(RecentChatsSidebar $event): void
    {
        logger($event->recentChatsSidebar);
    }
}

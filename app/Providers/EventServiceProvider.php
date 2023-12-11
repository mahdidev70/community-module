<?php

namespace TechStudio\Community\app\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use TechStudio\Community\app\Events\AddChatroomMember;
use TechStudio\Community\app\Events\CoverChatRoom;
use TechStudio\Community\app\Events\DeleteChatMessage;
use TechStudio\Community\app\Events\EditDescriptionChatroom;
use TechStudio\Community\app\Events\NewChatMessage;
use TechStudio\Community\app\Events\RecentChatsSidebar;
use TechStudio\Community\app\Events\RemoveChatroomMember;
use TechStudio\Community\app\Events\UpdateChatMessage;
use TechStudio\Community\app\Listeners\SendAddingMemberChatroomToClients;
use TechStudio\Community\app\Listeners\SendCoverChatroomToClients;
use TechStudio\Community\app\Listeners\SendDeleteChatMessageToClients;
use TechStudio\Community\app\Listeners\SendDescriptionChatroomToClients;
use TechStudio\Community\app\Listeners\SendNewChatMessageToClients;
use TechStudio\Community\app\Listeners\SendRecentChatsSidebar;
use TechStudio\Community\app\Listeners\SendRemovingMemberChatroomToClients;
use TechStudio\Community\app\Listeners\SendUpdateChatMessageToClients;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        NewChatMessage::class => [
            SendNewChatMessageToClients::class,
        ],
        UpdateChatMessage::class => [
            SendUpdateChatMessageToClients::class,
        ],
        DeleteChatMessage::class => [
            SendDeleteChatMessageToClients::class,
        ],
        CoverChatRoom::class => [
            SendCoverChatroomToClients::class,
        ],
        EditDescriptionChatroom::class => [
            SendDescriptionChatroomToClients::class,
        ],
        RemoveChatroomMember::class => [
            SendRemovingMemberChatroomToClients::class
        ],
        AddChatroomMember::class => [
            SendAddingMemberChatroomToClients::class
        ],
        RecentChatsSidebar::class => [
            SendRecentChatsSidebar::class
        ]
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

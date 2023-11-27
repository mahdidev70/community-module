<?php

namespace TechStudio\Community\app\Services\Chat;

use App\Events\RecentChatsSidebar;
use App\Models\ChatRoom;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatService
{
    public function getSidebar() {

        $rooms = \Auth::user()->chatrooms()->latest()->with('category', 'messages')->withCount('messages')->get()
        ->map(fn($room) => [
            'id' => $room->id,
            'slug' => $room->slug,
            'title' => $room->title,
            'avatarUrl' => $room->avatar_url,
            'description' => $room->description,
            'category' => $room->category?->slug,
            'unreadCount' => $room->pivot->unread_count,
            'lastMessage' => [
                'date' => isset($room->messages->last()->created_at) ? $room->messages->last()->created_at : '',
                'text' => isset($room->messages->last()->message) ? $room->messages?->last()->message : '',
                'sender' => [
                    'displayName' => isset($room->messages?->last()->user) ? $room->messages?->last()->user->getDisplayName() : '',
                ],
                'hasAttachment' => isset($room->messages?->last()->attachments) ? $room->messages?->last()->attachments : '',
            ],
        ])->toArray();
        usort($rooms, fn($a, $b) => $a['lastMessage']['date'] < $b['lastMessage']['date']);
        return ["rooms" => $rooms];
    }

    public function incrementUnreadCount($loginUser,$chatRoomId)
    {
        DB::table('chat_room_memberships')
            ->where('chat_room_id', $chatRoomId)
            ->where('user_id', '!=', $loginUser)
            ->increment('unread_count');
    }
    public function decrementUnreadCount($loginUser,$chatRoomId)
    {
        DB::table('chat_room_memberships')
            ->where('chat_room_id', $chatRoomId)
            ->where('user_id', $loginUser)
            ->update(['unread_count' => 0]);
            /*->decrement('unread_count');*/
    }
    public function recentChatsSidebar($room,$loginUser) {
        $users = $room->members()->where('user_id', '!=', $loginUser)->get();
            $memberIds = $users->pluck('id');
            $rooms = Chatroom::with('category', 'messages','members')
               ->whereHas('members', function ($query) use ($memberIds) {
                    $query->whereIn('user_id', $memberIds);
                })
                ->withCount('messages')
                ->latest()
                ->get()
                ->map(fn($room) => [
                    'id' => $room->id,
                    'slug' => $room->slug,
                    'title' => $room->title,
                    'avatarUrl' => $room->avatar_url,
                    'description' => $room->description,
                    'category' => $room->category?->slug,
                    'unreadCount' => $room->unreadCountMessage()??0,
                    'lastMessage' => [
                        'date' => isset($room->messages->last()->created_at) ? $room->messages->last()->created_at : '',
                        'text' => isset($room->messages->last()->message) ? $room->messages?->last()->message : '',
                        'sender' => [
                            'displayName' => isset($room->messages?->last()->user) ? $room->messages?->last()->user->getDisplayName() : '',
                        ],
                        'hasAttachment' => isset($room->messages?->last()->attachments) ? $room->messages?->last()->attachments : '',
                    ],
                ])->toArray();

            usort($rooms, fn($a, $b) => $a['lastMessage']['date'] < $b['lastMessage']['date']);
            foreach ($memberIds as $userId){
                RecentChatsSidebar::dispatch($userId, $rooms);
            }
    }
}

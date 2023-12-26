<?php

namespace TechStudio\Community\app\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use TechStudio\Community\app\Events\UpdateChatMessage;
use TechStudio\Community\app\Http\Requests\ReactMessageRequest;
use TechStudio\Community\app\Models\ChatMessage;
use TechStudio\Community\app\Models\ChatMessageReact;

class ChatMessageReactController extends Controller
{
    //
    public function __construct(protected ChatMessageReact $chatReactObj)
    {

    }
    public function saveChatReact(ReactMessageRequest $request)
    {
        $message = ChatMessage::where('id', $request->chatMessageId)->firstOrFail();

        // Get the room associated with the chat message
        $room = $message->room;
        // Get the users in the room
        $usersInRoom = $room->members;
        // Check if a specific user is in the room
        $userId = Auth::user()->id;
        $isUserInRoom = $usersInRoom->contains('id', $userId);
        if (!$isUserInRoom){
            return response()->json([
                'message' => 'مجاز به ارسال ری اکشن به این پیام نیستید.',
            ], 400);
        }

        if ($message->user_id == $userId) {
            return response()->json([
                'message' => 'Cannot react to your own message',
            ], 400);
        }
        $currentUserReaction = null;
        if ($request->reaction === 'clear'){
            $reactionRemove = $this->chatReactObj->removeReactionByUser($request);
        } else {
            $reactionSave = $this->chatReactObj->saveReactionByUser($request);
            $currentUserReaction = $reactionSave->reaction;
        }

        $reactions = $this->chatReactObj->totalReactions($request->chatMessageId);

        UpdateChatMessage::dispatch($message->room_id, [
            'id' => $message->id,
            'reactions' => [
                'currentUserReaction' => $currentUserReaction,
                'totalReactions' => $reactions,
           ]
        ]);
        return [
            "currentUserReaction" => $currentUserReaction,
            'totalReactions' => $reactions
        ];

    }

}

<?php

namespace App\Http\Controllers\Community\Forum;

use App\Events\UpdateChatMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Community\Forum\ReactMessageRequest;
use App\Models\ChatMessage;
use App\Models\ChatMessageReact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatMessageReactController extends Controller
{
    //
    public function __construct(protected ChatMessageReact $chatReactObj)
    {

    }
    public function saveChatReact(ReactMessageRequest $request)
    {
        $message = ChatMessage::where('id', $request->chatMessageId)->firstOrFail();
        if ($message->user_id == Auth::user()->id) {
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

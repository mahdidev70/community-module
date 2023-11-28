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

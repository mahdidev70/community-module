<?php

namespace TechStudio\Community\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ChatMessageReact extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','chat_id', 'reaction'];

    public function saveReactionByUser($data)
    {
        $data["user_id"]= auth()->id();
        $data["chat_id"]= $data->chatMessageId;
        if ($react = $this->isReactBy($data)) {
            $react->update($data->all());
            return $react;
        };
        return $this->create($data->all());
    }

    public function removeReactionByUser($request)
    {
        return self::where('user_id', auth()->id())->where('chat_id',$request->chatMessageId)->delete();
    }
    //if user react remove previous and add new react
    public function totalReactions($chatMessageId)
    {
        return self::where('chat_id',$chatMessageId)->groupBy('reaction')
            ->selectRaw('count(*) as total, reaction')
            ->get();
    }

    public function isReactBy($request)
    {
        return self::where('chat_id',$request->chat_id)
            ->where('user_id',$request->user_id)
            ->first();
    }
}

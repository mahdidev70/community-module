<?php

namespace TechStudio\Community\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use TechStudio\Core\app\Models\Traits\Attachable;
use TechStudio\Core\app\Models\UserProfile;

class ChatMessage extends Model
{
    use HasFactory , Attachable, SoftDeletes;

    protected $table = 'community_chat_messages';

    protected $guarded = ['id'];

    public function user() {
        return $this->belongsTo(UserProfile::class, 'user_id');  # FIXME CRITICAL n+1???
    }

    public function reply_to_object() {
        return $this->belongsTo(ChatMessage::class, 'reply_to')->with('user');  # FIXME CRITICAL n+1???
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ChatMessageReact::class,'chat_id','id');
    }

    public function reactionsCountByMsg()
    {
        return $this->reactions()->groupBy('reaction')
            ->selectRaw('count(*) as total, reaction')
            ->get();
    }

    public function current_user_feedback() 
    {
        $user_id = Auth::user()->id;
        return $this->reactions()->where('chat_id',$this->id)->where('user_id', $user_id)->pluck('reaction')->first();
    }

}

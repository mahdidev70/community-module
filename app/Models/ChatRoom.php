<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;


class ChatRoom extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable=['category_id','course_id','title','is_private','status','avatar_url','banner_url','description','slug'];
    public function getRouteKeyName()
    {
        return 'slug';
    }
    public function messages() {
        return $this->hasMany(ChatMessage::class, 'room_id');
    }

    public function category() {
        return $this->belongsTo(Category::class, 'category_id')->where('status','active');
    }

    public function course() {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function members() {
        return $this->belongsToMany(UserProfile::class, 'chat_room_memberships', 'chat_room_id', 'user_id')->where('status','active');
    }

    public function previewMembers() {
        return $this->belongsToMany(UserProfile::class, 'chat_room_memberships', 'chat_room_id', 'user_id')->where('status','active');
    }

    public function unreadCountMessage() {
        return $this->belongsToMany(UserProfile::class, 'chat_room_memberships', 'chat_room_id', 'user_id')->select('unread_count')->pluck('unread_count')->first();
    }

   /* protected static function boot()
    {
        parent::boot();

        if (!request()->is(['api/community/*', 'api/panel/*'])) {
            static::addGlobalScope('publiclyVisible', function (Builder $builder) {
                $builder->where('status', 'active');
            });
        }
    }*/
}

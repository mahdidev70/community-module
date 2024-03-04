<?php

namespace TechStudio\Community\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use TechStudio\Core\app\Models\Category;
use TechStudio\Core\app\Models\UserProfile;
use TechStudio\Lms\app\Models\Course;


class ChatRoom extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'community_chat_rooms';

    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        if (!request()->is(['*/api/panel/*'])) {
            static::addGlobalScope('publiclyVisible', function (Builder $builder) {
                $builder->where('status', 'active');
            });
        }
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
    public function messages() {
        return $this->hasMany(ChatMessage::class, 'room_id');
    }

    public function category() {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function course() {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function members() {
        return $this->belongsToMany(UserProfile::class, 'community_chat_room_memberships', 'chat_room_id', 'user_id','id','user_id')->where('status','active');
    }

    public function previewMembers() {
        return $this->belongsToMany(UserProfile::class, 'community_chat_room_memberships', 'chat_room_id', 'user_id','id','user_id')->where('status','active');
    }

    public function unreadCountMessage() {
        return $this->belongsToMany(UserProfile::class, 'community_chat_room_memberships', 'chat_room_id', 'user_id','id','user_id')->select('unread_count')->pluck('unread_count')->first();
    }

}

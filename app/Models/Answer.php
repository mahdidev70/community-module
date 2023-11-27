<?php

namespace TechStudio\Community\app\Models;

use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TechStudio\Core\app\Models\Traits\Attachable;
use TechStudio\Core\app\Models\Traits\Likeable;

class Answer extends Model
{
    use HasFactory, Likeable, Attachable;

    protected $fillable = ['question_id', 'user_id', 'text','likes_count','dislikes_count','status'];

    public function user() {
        return $this->belongsTo(UserProfile::class, 'user_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function dislikes()
    {
        return $this->morphMany(Like::class, 'likeable')->where('action', 'dislike');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable')->where('action', 'like');
    }

    protected static function boot()
    {
        parent::boot();

        if (!request()->is(['api/community/*', 'api/panel/*'])) {
            static::addGlobalScope('publiclyVisible', function (Builder $builder) {
                $builder->where('status', 'approved');
            });
        }
    }
}

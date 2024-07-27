<?php

namespace TechStudio\Community\app\Models;

use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TechStudio\Core\app\Models\Like;
use TechStudio\Core\app\Models\Traits\Attachable;
use TechStudio\Core\app\Models\Traits\Likeable;
use TechStudio\Core\app\Models\UserProfile;
use Illuminate\Database\Eloquent\SoftDeletes;

class Answer extends Model
{
    use HasFactory, Likeable, Attachable, SoftDeletes;

    protected $table = 'community_answers';

    protected $guarded = ['id'];

    public function user() {
        return $this->belongsTo(UserProfile::class, 'user_id','user_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function getDislikes()
    {
        return $this->morphMany(Like::class, 'likeable')->where('action', 'dislike');
    }

    public function getLikes()
    {
        return $this->morphMany(Like::class, 'likeable')->where('action', 'like');
    }

    protected static function boot()
    {
        parent::boot();

        if (!request()->is(['*/api/community/*', '*/api/panel/*'])) {
            static::addGlobalScope('publiclyVisible', function (Builder $builder) {
                $builder->where('status', 'approved');
            });
        }
    }
}

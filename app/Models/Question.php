<?php

namespace TechStudio\Community\app\Models;

use App\Models\Traits\Attachable;
use App\Models\Traits\Likeable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Question extends Model
{
    use HasFactory, Attachable, Likeable;

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id')->where('table_type', get_class($this));
    }

    public function asker()
    {
        return $this->belongsTo(UserProfile::class, 'asker_user_id');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class)->where('status', 'approved');
    }

    public function allAnswers()
    {
        return $this->hasMany(Answer::class);
    }

    public function topAnswers()
    {
        return $this->answers()->latest()->with('user')->take(4);
    }


    public function dislikes()
    {
        return $this->morphMany(Like::class, 'likeable')->where('action', 'dislike');
    }


    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable')->where('action', 'like');
    }

    /*protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('publiclyVisible', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where('asker_user_id', Auth::user()->id)->orWhere('status', 'approved');
            } else {
                $builder->where('status', 'approved');
            }
        });
    }*/
}

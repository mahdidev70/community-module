<?php

namespace TechStudio\Community\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TechStudio\Core\app\Models\Traits\Attachable;
use TechStudio\Core\app\Models\Traits\Likeable;
use TechStudio\Core\app\Models\UserProfile;

class Join extends Model
{
    use HasFactory, Likeable, Attachable;

    protected $table = 'community_joins';

    protected $guarded = ['id'];

    public function user() {
        return $this->belongsTo(UserProfile::class, 'user_id','user_id');
    }

    public function room() {
        return $this->belongsTo(ChatRoom::class);
    }

}

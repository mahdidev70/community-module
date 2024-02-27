<?php

namespace TechStudio\Community\app\Repositories;

use Illuminate\Support\Facades\Auth;
use TechStudio\Community\app\Models\Answer;
use TechStudio\Community\app\Models\Join;
use TechStudio\Community\app\Repositories\Interfaces\AnswerRepositoryInterface;
use TechStudio\Community\app\Repositories\Interfaces\ChatroomRepositoryInterface;

class ChatroomRepository implements ChatroomRepositoryInterface
{

    public function joinViaLink($userId, $roomId, $link)
    {
        $joinobject = new Join();
        $joinobject->create([
            'user_id' => $userId,
            'room_id' => $roomId,
            'link' => $link,
            'status' => 'waiting_for_approval',
        ]);
    }

    public function findByUserRoom($userId, $roomId)
    {
        $joinobject = new Join();
        return $joinobject->where('user_id',$userId)->where('room_id',$roomId)->first();
    }
}

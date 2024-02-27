<?php

namespace TechStudio\Community\app\Repositories;

use Illuminate\Support\Facades\Auth;
use TechStudio\Community\app\Models\Answer;
use TechStudio\Community\app\Models\Join;
use TechStudio\Community\app\Repositories\Interfaces\AnswerRepositoryInterface;
use TechStudio\Community\app\Repositories\Interfaces\ChatroomRepositoryInterface;
use TechStudio\Community\app\Repositories\Interfaces\JoinRepositoryInterface;

class JoinRepository implements JoinRepositoryInterface
{
    public function joinViaLink($userId, $roomId, $link)
    {
        $joinobject = new Join();
       return $joinobject->create([
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

    public function getFilterUsersByLinkJoin($request)
    {
        $joinQuery = Join::query()->with(['user','room']);

        if ($request->filled('status')) {
            $status = $request->input('status');
            $joinQuery->where('status', 'like',  '%' . $status . '%');
        }
        if ($request->filled('user')) {
            $search = $request->input('search');
             $joinQuery->whereHas('user', function ($query) use ($search) {
                 $query->where('first_name', 'like',  '%'. $search . '%')
                     ->orWhere('last_name', 'like',  '%'. $search . '%');
             });
        }
        if ($request->filled('room')) {
            $search = $request->input('room');
             $joinQuery->whereHas('room', function ($query) use ($search) {
                 $query->where('title', 'like',  '%'. $search . '%');
             });
        }

        $sortKey = 'created_at'; // Default sort key
        if ($request->filled('sortKey')) {
            $sortKey = $request->sortKey;
        }

        $sortOrder= 'desc';
        if ($request->filled('sortOrder')) {
            $sortOrder = $request->sortOrder;
        }
        return $joinQuery->orderBy($sortKey, $sortOrder)->paginate(10);
    }

    public function editStatus($request)
    {
        return Join::whereIn('id', $request['ids'])->update(['status' => $request['status']]);
    }

    public function findById($id)
    {
        return Join::where('id',$id)->first();
    }
}

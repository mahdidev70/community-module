<?php

namespace TechStudio\Community\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use TechStudio\Community\app\Http\Resources\JoinListResource;
use TechStudio\Community\app\Models\ChatRoom;
use TechStudio\Community\app\Models\Join;
use TechStudio\Community\app\Models\Question;
use TechStudio\Community\app\Repositories\Interfaces\JoinRepositoryInterface;

class JoinController extends Controller
{
    public function __construct(protected JoinRepositoryInterface $joinRepository)
    {}
    public function generateJoinLink($locale,ChatRoom $room)
    {
        $url = url($locale.'/api/community/chat/join/' . $room->slug);
        $room->update([
            "join_link" => $url
        ]);
        return response()->json([
            "joinLink"=> $url,
            'message' => 'لینک جوین به اتاق باموفقیت تولید شد.'
        ],200);
    }

    public function commonData()
    {
        $counts = [
            'all' => Join::count(),
            'active' => Join::where('status', 'active')->count(),
            'waiting' => Join::where('status', 'waiting_for_approval')->count(),
            'reject' => Join::where('status', 'reject')->count(),
        ];
        $status = ['active', 'waiting_for_approval','reject'];
        return  [
            'counts' => $counts,
            'status' => $status,
        ];
    }

    public function listData(Request $request)
    {
        $userJoin = $this->joinRepository->getFilterUsersByLinkJoin($request);
        return new JoinListResource($userJoin);
    }
}

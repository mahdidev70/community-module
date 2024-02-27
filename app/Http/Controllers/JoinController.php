<?php

namespace TechStudio\Community\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use TechStudio\Community\app\Events\AddChatroomMember;
use TechStudio\Community\app\Events\RemoveChatroomMember;
use TechStudio\Community\app\Http\Requests\UpdateJoinUserStatusRequest;
use TechStudio\Community\app\Http\Resources\JoinListResource;
use TechStudio\Community\app\Models\ChatRoom;
use TechStudio\Community\app\Models\Join;
use TechStudio\Community\app\Models\Question;
use TechStudio\Community\app\Repositories\Interfaces\JoinRepositoryInterface;
use TechStudio\Core\app\Models\UserProfile;

class JoinController extends Controller
{
    public function __construct(protected JoinRepositoryInterface $joinRepository)
    {}
    public function generateJoinLink($locale,ChatRoom $room)
    {
        $url = url($locale.'/api/community/chat/join/' . $room->slug);
        //$link = route('join.chatroom').'/'. $room->slug;
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
        $status = ['accept', 'waiting_for_approval','reject'];
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

    public function updateStatus(UpdateJoinUserStatusRequest $request)
    {
        try {
            //change status
            $this->joinRepository->editStatus($request);
            //add to chatroommember
            if ($request['status'] == 'accept'){
                foreach ($request['ids'] as $joinId){
                    $join = $this->joinRepository->findById($joinId);
                    $chatroom = ChatRoom::where('id',$join->room_id)->first();
                    $member = UserProfile::where('user_id',$join->user_id)->where('status','active')->first();
                    $chatroom->members()->attach($member->user_id);
                    $memberCount =  $chatroom->members()->count();

                    AddChatroomMember::dispatch($chatroom->id,[
                        'id' => $member->user_id,
                        "displayName" => $member->getDisplayName(),
                        'avatarUrl' => $member->avatar_url,
                    ],  $memberCount);
                }
            }
            if ($request['status'] == 'reject' || $request['status'] == 'waiting_for_approval'){
                foreach ($request['ids'] as $joinId){
                    $join = $this->joinRepository->findById($joinId);
                    $chatroom = ChatRoom::where('id',$join->room_id)->first();
                    $member = UserProfile::where('user_id',$join->user_id)->where('status','active')->first();
                    $chatroom->members()->detach($member->user_id);
                    $memberCount =  $chatroom->members()->count();

                    RemoveChatroomMember::dispatch($chatroom->id,[
                        "id" => $member->id,
                        "displayName" => $member->getDisplayName(),
                        'avatarUrl' => $member->avatar_url,
                        "memberCount" => $memberCount
                    ]);
                }
            }
            return response()->json(['message'=>'Ok']);
        }catch (\Exception){
            return 'Something is wring';
        }
    }
}

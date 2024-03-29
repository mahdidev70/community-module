<?php

namespace TechStudio\Community\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use TechStudio\Community\app\Models\ChatRoom;
use TechStudio\Community\app\Models\Question;
use TechStudio\Core\app\Models\Category;

class CommunityHomePageController extends Controller
{
    //
    public function getHomepageCommonData(Request $request)
    {
        $userRoomsCount = 0;
        $questionModel = new Question();
        $categories = Category::where('table_type', get_class($questionModel))->get(['slug','title']);
        $suggestedChatRooms = ChatRoom::where('status', 'active')->take(3)->latest()->with('category', 'previewMembers')->withCount('members')->get()
        ->map(fn($room) => [
            'roomId' => $room->id,
            'slug' => $room->slug,
            'title' => $room->title,
            'description' => $room->description,
            'isPrivate' => $room->is_private,
            'membersCount' => $room->members_count,
            'avatarUrl' => $room->avatar_url,
            'bannerUrl' => $room->banner_url,
            'category' => [
                "slug" => $room->category?->slug,
                "title" => $room->category?->title
            ],
            'previewedMembers' => $room->previewMembers->take(4)->map( fn ($userProfile) => [
                'id' => $userProfile->id,
                'displayName' => $userProfile->getDisplayName(),
                'avatarUrl' => $userProfile->avatar_url,
            ]),
        ]);

        $user_rooms = [];
        $user_rooms_count = 0;

        if (Auth('sanctum')->user()){
            $user = Auth('sanctum')->user();
            $user_rooms = $user->chatRooms()->with('category', 'previewMembers')->withCount('members')->get()
                ->map(
                     fn ($room) => [
                     'roomId' => $room->id,
                     'slug' => $room->slug,
                     'title' => $room->title,
                     'membersCount' => $room->members_count,
                     'avatarUrl' => $room->avatar_url,
                     'isPrivate' => $room->is_private,
                      'description' => $room->description,
                     'previewedMembers' => $room->previewMembers->take(5)->map( fn ($userProfile) => [
                         'id' => $userProfile->id,
                         'displayName' => $userProfile->getDisplayName(),
                         'avatarUrl' => $userProfile->avatar_url,
                     ]),
                     'category' => [
                             "slug" => $room->category?->slug,
                             "title" => $room->category?->title
                     ]
                 ]);
            $user_rooms_count = $user_rooms->count();
        }
        $result = [
            'categories' => $categories,
            'suggestedChatRooms' =>  $suggestedChatRooms,
            'publicRooms' => $suggestedChatRooms,  // For Demo AmirMahdi
            // 'publicRooms' => [],  // for backward compatibility
            'userRooms' => $user_rooms,
            'userChatRoomsCount' => $user_rooms_count,
        ];

        return $result;

    }
}

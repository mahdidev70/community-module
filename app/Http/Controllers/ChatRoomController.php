<?php

namespace TechStudio\Community\app\Http\Controllers;

use App\Helper\SlugGenerator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use TechStudio\Community\app\Events\AddChatroomMember;
use TechStudio\Community\app\Events\CoverChatRoom;
use TechStudio\Community\app\Events\DeleteChatMessage;
use TechStudio\Community\app\Events\EditDescriptionChatroom;
use TechStudio\Community\app\Events\NewChatMessage;
use TechStudio\Community\app\Events\RecentChatsSidebar;
use TechStudio\Community\app\Events\RemoveChatroomMember;
use TechStudio\Community\app\Events\UnreadCountMember;
use TechStudio\Community\app\Http\Requests\AddRemoveMemberRequest;
use TechStudio\Community\app\Http\Requests\CreateRoomRequest;
use TechStudio\Community\app\Http\Requests\EditDescriptionRequest;
use TechStudio\Community\app\Http\Requests\RoomCoverRequest;
use TechStudio\Community\app\Http\Requests\UpdateRoomRequest;
use TechStudio\Community\app\Http\Requests\UpdateRoomStatusRequest;
use TechStudio\Community\app\Http\Resources\ChatRoomResource;
use TechStudio\Community\app\Models\ChatMessage;
use TechStudio\Community\app\Models\ChatRoom;
use TechStudio\Community\app\Models\ChatRoomMembership;
use TechStudio\Community\app\Services\ChatService;
use TechStudio\Core\app\Models\Category;
use TechStudio\Core\app\Models\UserProfile;
use TechStudio\Core\app\Services\Category\CategoryService;
use TechStudio\Core\app\Services\File\FileService;

class ChatRoomController extends Controller
{
    public function __construct(protected FileService $fileService, protected ChatService $chatService,protected CategoryService $categoryService)
    {}

    public function getSingleChatPageCommonData($local, $category_slug, $chat_slug) {
        $category_id = Category::where('slug', $category_slug)->where('status','active')->firstOrFail()->id;
        $room = ChatRoom::where('category_id', $category_id)
            ->where('status','active')
            ->where('slug', $chat_slug)
            ->with(['previewMembers', 'category'])
            ->withCount('members')
            ->firstOrFail();
        return [
            'roomId' => $room->id,
            'name' => $room->title,
            'category' => [
                'slug' => $room->category->slug,
                'title' => $room->category->title,
            ],
            'is_private' => $room->is_private,
            'membersCount' => $room->members_count,
            'avatarUrl' => $room->avatar_url,
            'bannerUrl' => $room->banner_url,
            'membersListSummary' => $room->previewMembers->take(5)->map( fn($membership) => [
                'id' => $membership->id,
                'displayName' => $membership->getDisplayName(),
                'secondaryText' => $membership->email,
                'avatarUrl' => $membership->avatar_url,
            ]),
            'otherRooms' => $this->getOtherRooms($chat_slug),
            'description' => $room->description
        ];
    }

    public function getSingleChatPageMessages($local, $category_slug, $chat_slug)
    {
        $user = Auth::user();
        $category_id = Category::where('slug', $category_slug)->where('status','active')->firstOrFail()->id;
        $room = ChatRoom::where('category_id', $category_id)->where('slug', $chat_slug)->where('status','active')->firstOrFail();
        $this->chatService->decrementUnreadCount($user->id,$room->id);
        $allow = $room->members()->where('core_user_profiles.user_id', $user->id)->exists();
        $page = $room->messages()->latest()->with('user', 'reply_to_object', 'attachments')->paginate();
        UnreadCountMember::dispatch($room->id, $user->id);
        $page->through(function($a){

            return [
                'id' => $a->id,
                'userId' => $a->user_id,
                'userDisplayName' => $a->user->getDisplayName(),
                'stars' => null,
                'avatarUrl' => $a->user->avatar_url,
                'doubleChecks' => $a->is_seen,
                'date' => $a->created_at,
                'message' => $a->message,
                'replyTo' => $a->reply_to_object ? [
                    'id' => $a->reply_to_object->id,
                    'userDisplayName' => $a->reply_to_object->user->getDisplayName(),
                    'userId' => $a->reply_to_object->user->id,
                    'message' => $a->reply_to_object->message,
                ] : null,
                'reactions' => [
                    "totalReactions" => $a->reactionsCountByMsg(),
                    "currentUserReaction" => $a->current_user_feedback()
                    ],
                'attachments' => $a->attachments->map(fn ($file) => [
                    'id' => $file->id,
                    'type' => 'image',  // TODO: infer
                    'previewImageUrl' => $file->file_url,
                    'contentUrl' => $file->file_url,
                ])
            ];
        });
        return [
            "loginUserAllowToChat" =>$allow,
            "data" => $page
        ];
    }

    private function getOtherRooms($chatRoomSlug)
    {
        $otherRooms = ChatRoom::where('slug','<>',$chatRoomSlug)->with('previewMembers','category')->withCount('members')->take(5)->get();
        return $otherRooms->map(fn ($room) => [
            'roomId' => $room->id,
            'name' => $room->title,
            'membersCount' => $room->members_count,
            'isPrivate' => $room->is_private,
            'avatarUrl' => $room->avatar_url,
            'slug' => $room->slug,
            'description' => $room->description??'',
            'category' => [
                'slug' => $room->category->slug,
                'title' => $room->category->title,
            ],
            'previewedMembers' => $room->previewMembers->take(5)->map( fn ($userProfile) => [
                'id' => $userProfile->id,
                'displayName' => $userProfile->getDisplayName(),
                'avatarUrl' => $userProfile->avatar_url,
            ])
        ]);
    }

    public function postChatMessage($local, Category $category_slug,ChatRoom $room, Request $request)
    {
        $swearProbability = null;
        if(!$this->userCanChangeRoomInfo($room)){
            return response()->json([
                'message' => 'برای تغییر در اتاق باید عضو اتاق باشید.',
            ], 400);
        }
        try {
            $response = Http::timeout(1)->get("http://swear_detection:5001/" . $request->message)->json();
            $swearProbability = $response['swear_probability'];
        } catch (\Exception $e) {
            \Log::warning('Recommendation system error. Reason: ' . $e);
            $swearProbability = 0.0;
        }
        if ($swearProbability > 0.95) {
            \Log::warning('Blocking swear word in: ' . $request->message);
            throw new BadRequestException("قابلیت ارسال پیام وجود ندارد.");
        }

        $lastMessage = ChatMessage::where('room_id', $room->id)->latest()->first();
        if ($request->replyTo) {
            $reply_to_object = ChatMessage::where('room_id', $room->id)->where('id', $request->replyTo)->with('user')->firstOrFail();
        }
        $user = Auth::user();
        $message = new ChatMessage();
        $message->room_id = $room->id;
        $message->user_id = $user->id;
        $message->message = $request->message;
        $message->reply_to = $request->replyTo;
        $message->save();
        if ($request->attachments) {
            $message->file_attachments = $message->associateAttachments($request->attachments);
        }

        $this->chatService->incrementUnreadCount($user->id,$room->id);

        NewChatMessage::dispatch($room->id, $lastMessage->id, [
            'id' => $message->id,
            'userId' => $user->id,
            'userDisplayName' => $user->getDisplayName(),
            'stars' => null,
            'avatarUrl' => $user->avatar_url,
            'doubleChecks' => $message->is_seen,
            'date' => $message->created_at,
            'message' => $message->message,
            'replyTo' => $request->replyTo ? [
                'id' => $reply_to_object->id,
                'userDisplayName' => $reply_to_object->user->getDisplayName(),
                'userId' => $reply_to_object->user->id,
                'message' => $reply_to_object->message,
            ] : null,
            'reactions' => $message->reactionsCountByMsg(),
            'attachments' => $message->file_attachments
        ]);
        # event update sidebar chat
        $this->chatService->recentChatsSidebar($room,$user->id);
        return response(['id' => $message->id], 201);
    }

    public function getChatRoomMembers($local, $category_slug, $chat_slug)
    {
        $category_id = Category::where('slug', $category_slug)->where('status','active')->firstOrFail()->id;
        $room = ChatRoom::where('category_id', $category_id)->where('slug', $chat_slug)->where('status','active')->firstOrFail();
        $response = $room->members()->paginate(10)->through( fn ($member) => [
            'id' => $member->id,
            'avatarUrl' => $member->avatar_url,
            'displayName' => $member->getDisplayName(),
            'secondaryText' => $member->email,
        ]);
        return $response;
    }

    public function newAttachment(Request $request) {
        $createdFiles = $this->fileService->upload(
            $request,
            max_count: 3,
            max_size_mb: 10,
            types: ['jpg', 'jpeg', 'png'],
            format_result_as_attachment: true,
            storage_key: 'community',
        );
        return response()->json($createdFiles);
    }

    public function deleteMessage($local, $message_id)
    {
        $message = ChatMessage::where('id', $message_id)->firstOrFail();
        return $message;

        if (!$message) {
            return response()->json(['message' => 'Message not found'], 404);
        }

        $message->attachments()->delete();
        $message->delete();

        DeleteChatMessage::dispatch($message->room_id, [
            'id' => $message->id,
            'message' => $message->message,
        ]);

        return response()->json([
            'message' => 'Message deleted successfully', 'message_id' => $message_id
        ]);
    }

    public function recentChatsSidebar(Request $request) {
        $userId = Auth::user()->id;
        $message = $this->chatService->getSidebar();

        RecentChatsSidebar::dispatch($userId, $message['rooms']);
        return $message;
    }

    public function getChatRoomsData(Request $request)
    {
        $query = ChatRoom::where('status', 'active')->with(['previewMembers', 'category']);

        if ($request->anyFilled(['search', 'categorySlug'])) {
            if ($request->filled('search')) {
                $txt = $request->get('search');
                $query->where(function ($q) use ($txt) {
                    $q->where('title', 'like', '%' . $txt . '%');
                });
            }
            if (isset($request->categorySlug) && $request->categorySlug != 'all') {
                $query->whereHas('category', function ($categoryQuery) use ($request) {
                    $categoryQuery->where('slug', $request->input('categorySlug'));
                });
            }
            $rooms = $query;
        }else{
            $rooms = $query->inRandomOrder();
        }
        $rooms =  $rooms->withCount('members')
            ->paginate(12);
        $data = $rooms->map(fn ($room) => [
                'roomId' => $room->id,
                'slug' => $room->slug,
                'title' => $room->title,
                'category' => [
                    'slug' => $room->category->slug,
                    'title' => $room->category->title,
                ],
                'is_private' => $room->is_private,
                'membersCount' => $room->members_count,
                'avatarUrl' => $room->avatar_url,
                'bannerUrl' => $room->banner_url,
                'membersListSummary' => $room->previewMembers->take(5)->map( fn($membership) => [
                    'id' => $membership->id,
                    'displayName' => $membership->getDisplayName(),
                    'secondaryText' => $membership->email,
                    'avatarUrl' => $membership->avatar_url,
                ]),
                'otherRooms' => $this->getOtherRooms($room->slug),
                'description' => $room->description
            ]);

        return response()->json([
            'data' => $data,
            'total' => $rooms->total(),
            'current_page' => $rooms->currentPage(),
            'per_page' => $rooms->perPage(),
            'last_page' => $rooms->lastPage(),
        ]);
    }


    public function getChatRoomsCommon()
    {
        $topViewRoom = ChatRoom::where('status', 'active')->with(['previewMembers', 'category'])->orderBy('created_at', 'desc')
            ->take(2)
            ->get()->map(fn ($room) => [
                'roomId' => $room->id,
                'slug' => $room->slug,
                'title' => $room->title,
                'category' => [
                    'slug' => $room->category->slug,
                    'title' => $room->category->title,
                ],
                'is_private' => $room->is_private,
                'membersCount' => $room->members_count,
                'avatarUrl' => $room->avatar_url,
                'bannerUrl' => $room->banner_url,
                'membersListSummary' => $room->previewMembers->take(5)->map( fn($membership) => [
                    'id' => $membership->id,
                    'displayName' => $membership->getDisplayName(),
                    'secondaryText' => $membership->email,
                    'avatarUrl' => $membership->avatar_url,
                ]),
                'otherRooms' => $this->getOtherRooms($room->slug),
                'description' => $room->description
            ]);
        $categories =  $this->categoryService->getCategoriesForFilter(new ChatRoom());

        return response()->json([
            'top_rooms' => $topViewRoom,
            'categories' => $categories,
        ]);
    }

    public function getSearchChatRooms(Request $request)
    {
        $query = ChatRoom::with(['previewMembers', 'category']);
        $topViewRoom = $query->orderBy('created_at', 'desc')
            ->take(2)
            ->get()->map(fn ($room) => [
                'roomId' => $room->id,
                'slug' => $room->slug,
                'title' => $room->title,
                'category' => [
                    'slug' => $room->category->slug,
                    'title' => $room->category->title,
                ],
                'is_private' => $room->is_private,
                'membersCount' => $room->members_count,
                'avatarUrl' => $room->avatar_url,
                'bannerUrl' => $room->banner_url,
                'membersListSummary' => $room->previewMembers->take(5)->map( fn($membership) => [
                    'id' => $membership->id,
                    'displayName' => $membership->getDisplayName(),
                    'secondaryText' => $membership->email,
                    'avatarUrl' => $membership->avatar_url,
                ]),
                'otherRooms' => $this->getOtherRooms($room->slug),
                'description' => $room->description
            ]);
        if($request->filled('search')) {
            $txt = $request->query->get('search');
            //toye title
            $rooms = $query->where(function ($q) use ($txt) {
                $q->where('title', 'like', '%' . $txt)->orWhere('title', 'like', '% ' . $txt . '%')->orWhere('title', 'like', $txt . '%');
            })->get()->map(fn ($room) => [
                'roomId' => $room->id,
                'slug' => $room->slug,
                'title' => $room->title,
                'category' => [
                    'slug' => $room->category->slug,
                    'title' => $room->category->title,
                ],
                'is_private' => $room->is_private,
                'membersCount' => $room->members_count,
                'avatarUrl' => $room->avatar_url,
                'bannerUrl' => $room->banner_url,
                'membersListSummary' => $room->previewMembers->take(5)->map( fn($membership) => [
                    'id' => $membership->id,
                    'displayName' => $membership->getDisplayName(),
                    'secondaryText' => $membership->email,
                    'avatarUrl' => $membership->avatar_url,
                ]),
                'otherRooms' => $this->getOtherRooms($room->slug),
                'description' => $room->description
            ]);
            return response()->json([
                'top_rooms' => $topViewRoom,
                'rooms' => $rooms
            ]);
        }
        return response()->json([
            'top_rooms' => $topViewRoom,
            'rooms' => []
        ]);
    }

    public function uploadCover($local,ChatRoom $chat_slug, RoomCoverRequest $request)
    {
        if(!$this->userCanChangeRoomInfo($chat_slug)){
            return response()->json([
                'message' => 'برای تغییر در اتاق باید عضو اتاق باشید.',
            ], 400);
        }
        $fileService = new FileService();
        $fileUrl = $fileService->uploadOneFile(
            $request,
            storage_key: 'community',
        );
        $chat_slug->update(['banner_url' => $fileUrl['url']]);
        CoverChatRoom::dispatch( $chat_slug->id,[
            "roomId" => $chat_slug->id,
            "title" => $chat_slug->title,
            "slug" => $chat_slug->slug,
            "bannerUrl" => $chat_slug->banner_url,
            "isPrivate" => $chat_slug->is_private,
            "status" => $chat_slug->status,
        ]);
        return response()->json([
            "id" => $chat_slug->id,
            "title" => $chat_slug->title,
            "slug" => $chat_slug->slug,
            "bannerUrl" => $chat_slug->banner_url,
            "isPrivate" => $chat_slug->is_private,
            "status" => $chat_slug->status,
            ]);
    }

    public function join(Request $request)
    {
        return ChatRoom::where('slug',$request->chat_slug)->with('previewMembers')->withCount('members')->firstOrFail();
    }

    public function addMember($local, ChatRoom $chat_slug, AddRemoveMemberRequest $request)
    {
        return $chat_slug;
        $member = $request->memberId;
        $chat_slug->members()->sync($member->id);
        $memberCount =  $chat_slug->members()->count();
        AddChatroomMember::dispatch($chat_slug->id,[
            'id' => $member->id,
            "displayName" => $member->getDisplayName(),
            'avatarUrl' => $member->avatar_url,
        ],  $memberCount);
        return response()->json([
            'room' => [
                'roomId' => $chat_slug->id,
                'name' => $chat_slug->title,
                'category' => [
                    'slug' => $chat_slug->category->slug,
                    'title' => $chat_slug->category->title,
                ],
                'is_private' => $chat_slug->is_private,
                'avatarUrl' => $chat_slug->avatar_url,
                'bannerUrl' => $chat_slug->banner_url,
                'description' => $chat_slug->description,
                'memberCount' => $memberCount
            ],
            'member' => [
                'id' => $member->id,
                'displayName' => $member->getDisplayName(),
                'avatarUrl' => $member->avatar_url,
            ],
        ]);
    }

    public function removeRoomMember($local, Category $category_slug, ChatRoom $chat_slug,AddRemoveMemberRequest $request)
    {
        if(!$this->userCanChangeRoomInfo($chat_slug)){
            return response()->json([
                'message' => 'برای تغییر در اتاق باید عضو اتاق باشید.',
            ], 400);
        }
        $member = UserProfile::find($request->memberId);
        $chat_slug->members()->detach($member->id);
        $memberCount =  $chat_slug->members()->count();
        RemoveChatroomMember::dispatch($chat_slug->id,[
            "id" => $member->id,
            "displayName" => $member->getDisplayName(),
            'avatarUrl' => $member->avatar_url,
            "memberCount" => $memberCount
        ]);
        return response()->json([
            'room' => [
               "id" => $chat_slug->id,
                "title" => $chat_slug->title,
                "slug" => $chat_slug->slug,
                "memberCount" => $memberCount
                ],
            'category' => [
                "id" => $category_slug->id,
                "title" => $category_slug->title,
                "slug" => $category_slug->slug,

            ],
        ]);
    }

    public function editRoomDescription($local, Category $category_slug, ChatRoom $room,EditDescriptionRequest $request)
    {
        if(!$this->userCanChangeRoomInfo($room)){
            return response()->json([
                'message' => 'برای تغییر در اتاق باید عضو اتاق باشید.',
            ], 400);
        }
        $room->update(['description' => $request->description]);

        $data =[
            'roomId' => $room->id,
            'slug' => $room->slug,
            'title' => $room->title,
            'category' => [
                'slug' => $room->category->slug,
                'title' => $room->category->title,
            ],
            'is_private' => $room->is_private,
            'membersCount' => $room->members_count,
            'avatarUrl' => $room->avatar_url,
            'bannerUrl' => $room->banner_url,
            'membersListSummary' => $room->previewMembers->take(5)->map( fn($membership) => [
                'id' => $membership->id,
                'displayName' => $membership->getDisplayName(),
                'secondaryText' => $membership->email,
                'avatarUrl' => $membership->avatar_url,
            ]),
            'otherRooms' => $this->getOtherRooms($room->slug),
            'description' => $room->description
        ];
        EditDescriptionChatroom::dispatch($room->id, $data);
        return response()->json($data);
    }

    public function getChatRoomsPannelCommon()
    {
        $categories =  $this->categoryService->getCategoriesForFilter(new ChatRoom());
        $counts = [
            'all' => ChatRoom::whereNot('status', 'inactive')->count(),
            'inactive' => ChatRoom::where('status', 'inactive')->count(),
            'draft' => ChatRoom::where('status', 'draft')->count(),
            'active' => ChatRoom::where('status', 'active')->count(),
        ];
        $counts_case = [
            'all' => ChatRoom::whereNot('status', 'inactive')->count(),
            'is_private' => ChatRoom::where('is_private', 1)->count(),
            'is_public' => ChatRoom::where('is_private', 0)->count(),
        ];


        return response()->json([
            'categories' => $categories,
            'counts_status' => $counts,
            'counts_position' => $counts_case, //is_private
        ]);

    }

    public function getChatRoomsPannelData(Request $request)
    {
        $query = ChatRoom::with(['previewMembers', 'category'])->withCount('members');

        if ($request->anyFilled(['search', 'categorySlug'])) {
            if ($request->filled('search')) {
                $txt = $request->get('search');
                $query->where(function ($q) use ($txt) {
                    $q->where('title', 'like', '%' . $txt . '%');
                });
            }
            if (isset($request->categorySlug) && $request->categorySlug != 'all') {
                $query->whereHas('category', function ($categoryQuery) use ($request) {
                    $categoryQuery->where('slug', $request->input('categorySlug'));
                });
            }
        }
        $rooms =  $query->paginate(12);
        $data = $rooms->map(fn ($room) => [
            'roomId' => $room->id,
            'slug' => $room->slug,
            'title' => $room->title,
            'category' => [
                'slug' => $room->category->slug,
                'title' => $room->category->title,
            ],
            'is_private' => $room->is_private,
            'membersCount' => $room->members_count,
            'avatarUrl' => $room->avatar_url,
            'bannerUrl' => $room->banner_url,
            'description' => $room->description
        ]);

        return [
            'total' => $rooms->total(),
            'per_page' => $rooms->perPage(),
            'last_page' => $rooms->lastPage(),
            'current_page' => $rooms->currentPage(),
            'data' => $data
        ];
    }

    public function updateRoomStatus(UpdateRoomStatusRequest $request)
    {
        ChatRoom::whereIn('id', $request['ids'])
        ->update(['status' => $request['status']]);
        return [
            'updatedRooms' => $request['ids'],
        ];
    }

    public function getCreatChatRoomsPannelCommon()
    {
        $categories =  $this->categoryService->getCategoriesForFilter(new ChatRoom());
        $users = UserProfile::where('status','active')->get()
                ->map(fn($user)=>[
                    'userId' => $user->id,
                    'userDisplayName' => $user->getDisplayName(),
                    'avatarUrl' => $user->avatar_url,
                ]);
        return response()->json([
            'categories' => $categories,
            'users' => $users,
        ]);
    }

    public function createChatRoomsPannel(CreateRoomRequest $request)
    {
        $data = $request->only(ChatRoom::getModel()->fillable);
        if ($request->hasFile('file')){
            $fileService = new FileService();
            $fileUrl = $fileService->uploadOneFile(
                $request,
                storage_key: 'community',
            );
            $data['banner_url'] = $fileUrl['url'];
        }
        $room = ChatRoom::create($data);
        if ($request->filled('members')) {
            $room->members()->attach($request->members);
        }
        return response()->json(
            $room
        );
    }

    public function deleteRoom($local, ChatRoom $slug)
    {
        $slug->members()->detach();
        $slug->delete();

        return response(
            "OK", 200);
    }

    public function getChatData($local, ChatRoom $room)
    {
        $data = [
            'roomId' => $room->id,
            'slug' => $room->slug,
            'title' => $room->title,
            'category' => [
                'slug' => $room->category->slug,
                'title' => $room->category->title,
            ],
            'is_private' => $room->is_private,
            'membersCount' => $room->members->count(),
            'avatarUrl' => $room->avatar_url,
            'bannerUrl' => $room->banner_url,
            /*'membersListSummary' => $room->previewMembers->take(5)->map(fn($membership) => [
                'id' => $membership->id,
                'displayName' => $membership->getDisplayName(),
                'secondaryText' => $membership->email,
                'avatarUrl' => $membership->avatar_url,
            ]),*/
            'description' => $room->description
        ];
        return response()->json($data);
    }

    public function updateChat($local, UpdateRoomRequest $request,ChatRoom $room)
    {
        $data = $request->only(ChatRoom::getModel()->fillable);
        if ($request->file('file')){
            $fileService = new FileService();
            $fileUrl = $fileService->uploadOneFile(
                $request,
                storage_key: 'community',
            );
            $data['banner_url'] = $fileUrl;
        }
        $room->update($data);
        if ($request->filled('members')) {
            $room->members()->sync($request->members);
        }
        return response()->json(
            $room
        );
    }

    public function getUserRoom()
    {
        $user = Auth::user();

        $chatRoomIds = ChatRoomMembership::where('user_id', $user->id)->pluck('chat_room_id');

        $myRoom = ChatRoom::whereIn('id', $chatRoomIds)->get();

        return [
            'myRoom' => ChatRoomResource::collection($myRoom),
        ];
    }
    public function userCanChangeRoomInfo($room)
    {
        $user = Auth::user();
        $checkUser = $room->members()->pluck('community_chat_room_memberships.user_id')->toArray();
        if (!$user){
            return response()->json([
                'message' => 'ابتدا وارد شوید.',
            ], 401);
        }
        if(!in_array($user->id, $checkUser)) {
            return false;
        }
        return true;
    }
}

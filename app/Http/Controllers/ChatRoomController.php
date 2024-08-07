<?php

namespace TechStudio\Community\app\Http\Controllers;

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
use TechStudio\Community\app\Http\Resources\ChatRoomsResource;
use TechStudio\Community\app\Models\ChatMessage;
use TechStudio\Community\app\Models\ChatRoom;
use TechStudio\Community\app\Models\ChatRoomMembership;
use TechStudio\Community\app\Repositories\Interfaces\JoinRepositoryInterface;
use TechStudio\Community\app\Services\ChatService;
use TechStudio\Core\app\Helper\SlugGenerator;
use TechStudio\Core\app\Models\Category;
use TechStudio\Core\app\Models\UserProfile;
use TechStudio\Core\app\Services\Category\CategoryService;
use TechStudio\Core\app\Services\File\FileService;

class ChatRoomController extends Controller
{
    public function __construct(
        protected FileService $fileService,
        protected ChatService $chatService,
        protected CategoryService $categoryService,
        protected JoinRepositoryInterface $joinRepository
    ) {
    }

    public function getSingleChatPageCommonData($locale, $category_slug, $chat_slug)
    {
        $category_id = Category::where('slug', $category_slug)->where('status', 'active')->firstOrFail()->id;

        $room = ChatRoom::where('category_id', $category_id)
            ->where('status', 'active')
            ->where('slug', $chat_slug)
            ->with(['previewMembers', 'category'])
            ->withCount('members')
            ->firstOrFail();
        $allow = false;
        if (Auth('sanctum')->user()) {
            $user = Auth('sanctum')->user();
            $allow = $room->members()->where('core_user_profiles.user_id', $user->id)->exists();
        }

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
            'joinLink' => $room->slug,
            'membersListSummary' => $room->previewMembers->take(5)->map(fn ($membership) => [
                'id' => $membership->user_id,
                'displayName' => $membership->getDisplayName(),
                'secondaryText' => $membership->email,
                'avatarUrl' => $membership->avatar_url,
            ]),
            'otherRooms' => $this->getOtherRooms($locale, $chat_slug),
            'description' => $room->description,
            "loginUserAllowToChat" => $allow
        ];
    }

    public function getPreviewSingleChatPageMessages()
    {
        $data = [
            'data' => [
                '0' => [
                    'id' => 55,
                    'userId' => 44,
                    'userDisplayName' => 'کاربر جدید',
                    'stars' => null,
                    'avatarUrl' => 'https://storage.sa-test.techstudio.diginext.ir/community-files/65e4a20586d69.png',
                    'doubleChecks' => 0,
                    'date' => '2024-03-03T09:44:38.000000Z',
                    'message' => 'راهکارتون برای فروش بیشتر چیه؟',
                    'replyTo' => null,
                    'reactions' => [
                        "totalReactions" => [],
                        "currentUserReaction" => []
                    ],
                    'attachments' => []
                ],
                '1' => [
                    'id' => 55,
                    'userId' => 44,
                    'userDisplayName' => 'کاربر جدید',
                    'stars' => null,
                    'avatarUrl' => 'https://storage.sa-test.techstudio.diginext.ir/community-files/65e4a20586d69.png',
                    'doubleChecks' => 0,
                    'date' => '2024-03-03T09:44:38.000000Z',
                    'message' => 'راهکارتون برای فروش بیشتر چیه؟',
                    'replyTo' => null,
                    'reactions' => [
                        "totalReactions" => [],
                        "currentUserReaction" => []
                    ],
                    'attachments' => []
                ],
                '2' => [
                    'id' => 55,
                    'userId' => 44,
                    'userDisplayName' => 'کاربر جدید',
                    'stars' => null,
                    'avatarUrl' => 'https://storage.sa-test.techstudio.diginext.ir/community-files/65e4a20586d69.png',
                    'doubleChecks' => 0,
                    'date' => '2024-03-03T09:44:38.000000Z',
                    'message' => 'راهکارتون برای فروش بیشتر چیه؟',
                    'replyTo' => null,
                    'reactions' => [
                        "totalReactions" => [],
                        "currentUserReaction" => []
                    ],
                    'attachments' => []
                ]
            ],
            'current_page' => 1,
            'first_page_url' => '',
            'from' => '',
            'last_page' => '',
            'last_page_url' => '',
            'links' => [],
            'next_page_url' => '',
            'path' => 'sdfdsf',
            'per_page' => 1,
            'prev_page_url' => null,
            'to' => 2,
            'total' => 10
        ];
        return [
            "loginUserAllowToChat" => false,
            "data" => $data
        ];
    }

    public function previewRecentChatsSidebar()
    {
        $result = [
            'rooms' =>   [
                "0" => [
                    "id" => 8,
                    "slug" => "فروشندگان-غرب-ایران",
                    "title" => "فروشندگان غرب ایران",
                    "avatarUrl" => "https://storage.sa-test.techstudio.diginext.ir/community-files/65e5ec9045fc0.png",
                    "description" => "در این اتاق، امکان بحرانی کردن در موضوعات جذاب و جدید در زمینه بازاریابی و تبادل دانش و تجربیات بازاریابان ماهر و علاقه‌مند وجود دارد. با شرکت در این گفتگوها، می‌توانید از اندیشه‌ها و استراتژی‌های دیگران بهره‌مند شده و به ارتقاء مهارت‌های بازاریابی خود بپردازید.",
                    "category" => "سراسر-ایران",
                    "unreadCount" => 2,
                    "lastMessage" => [
                        "date" => "2024-03-05T08:46:55.000000Z",
                        "text" => "ظاهرا مشکلی نداره بازم چک کن",
                        "sender" => [
                            "displayName" => "مهدی سمیعیان"
                        ],
                        "hasAttachment" => []
                    ]
                ],
                "1" => [
                    "id" => 8,
                    "slug" => "فروشندگان-غرب-ایران",
                    "title" => "فروشندگان غرب ایران",
                    "avatarUrl" => "https://storage.sa-test.techstudio.diginext.ir/community-files/65e5ec9045fc0.png",
                    "description" => "در این اتاق، امکان بحرانی کردن در موضوعات جذاب و جدید در زمینه بازاریابی و تبادل دانش و تجربیات بازاریابان ماهر و علاقه‌مند وجود دارد. با شرکت در این گفتگوها، می‌توانید از اندیشه‌ها و استراتژی‌های دیگران بهره‌مند شده و به ارتقاء مهارت‌های بازاریابی خود بپردازید.",
                    "category" => "سراسر-ایران",
                    "unreadCount" => 2,
                    "lastMessage" => [
                        "date" => "2024-03-05T08:46:55.000000Z",
                        "text" => "ظاهرا مشکلی نداره بازم چک کن",
                        "sender" => [
                            "displayName" => "مهدی سمیعیان"
                        ],
                        "hasAttachment" => []
                    ]
                ],
                "2" => [
                    "id" => 8,
                    "slug" => "فروشندگان-غرب-ایران",
                    "title" => "فروشندگان غرب ایران",
                    "avatarUrl" => "https://storage.sa-test.techstudio.diginext.ir/community-files/65e5ec9045fc0.png",
                    "description" => "در این اتاق، امکان بحرانی کردن در موضوعات جذاب و جدید در زمینه بازاریابی و تبادل دانش و تجربیات بازاریابان ماهر و علاقه‌مند وجود دارد. با شرکت در این گفتگوها، می‌توانید از اندیشه‌ها و استراتژی‌های دیگران بهره‌مند شده و به ارتقاء مهارت‌های بازاریابی خود بپردازید.",
                    "category" => "سراسر-ایران",
                    "unreadCount" => 2,
                    "lastMessage" => [
                        "date" => "2024-03-05T08:46:55.000000Z",
                        "text" => "ظاهرا مشکلی نداره بازم چک کن",
                        "sender" => [
                            "displayName" => "مهدی سمیعیان"
                        ],
                        "hasAttachment" => []
                    ]
                ]
            ]
        ];
        return $result;
    }

    public function getSingleChatPageMessages($locale, $category_slug, $chat_slug)
    {
        $user = auth()->user();
        $category_id = Category::where('slug', $category_slug)->where('status', 'active')->firstOrFail()->id;
        $room = ChatRoom::where('category_id', $category_id)->where('slug', $chat_slug)->where('status', 'active')->firstOrFail();
        $this->chatService->decrementUnreadCount($user->id, $room->id);
        $allow = $room->members()->where('core_user_profiles.user_id', $user->id)->exists();
        $page = $room->messages()->latest()->with('user', 'reply_to_object', 'attachments')->paginate();
        UnreadCountMember::dispatch($room->id, $user->id);
        $page->through(function ($a) {

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
                    'userId' => $a->reply_to_object->user->user_id,
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
            "loginUserAllowToChat" => $allow,
            "data" => $page
        ];
    }

    private function getOtherRooms($locale, $chatRoomSlug)
    {
        $otherRooms = ChatRoom::where('slug', '<>', $chatRoomSlug)->with('previewMembers', 'category')->withCount('members')->take(5)->get();

        return $otherRooms->map(fn ($room) => [
            'roomId' => $room->id,
            'name' => $room->title,
            'membersCount' => $room->members_count,
            'isPrivate' => $room->is_private,
            'avatarUrl' => $room->avatar_url,
            'slug' => $room->slug,
            'description' => $room->description ?? '',
            'category' => [
                'slug' => $room->category->slug,
                'title' => $room->category->title,
            ],
            'previewedMembers' => $room->previewMembers->take(5)->map(fn ($userProfile) => [
                'id' => $userProfile->user_id,
                'displayName' => $userProfile->getDisplayName(),
                'avatarUrl' => $userProfile->avatar_url,
            ])
        ]);
    }

    public function postChatMessage($locale, Category $category_slug, ChatRoom $room, Request $request)
    {
        $swearProbability = null;
        if ($this->userCanChangeRoomInfo($locale, $room) == false) {
            return response()->json([
                'message' => 'برای ارسال پیغام باید عضو اتاق باشید.',
            ], 400);
        }
        /*try {
            $response = Http::timeout(1)->get("http://swear_detection:5001/" . $request->message)->json();
            $swearProbability = $response['swear_probability'];
        } catch (\Exception $e) {
            \Log::warning('Recommendation system error. Reason: ' . $e);
            $swearProbability = 0.0;
        }
        if ($swearProbability > 0.95) {
            \Log::warning('Blocking swear word in: ' . $request->message);
            throw new BadRequestException("قابلیت ارسال پیام وجود ندارد.");
        }*/

        $lastMessage = ChatMessage::where('room_id', $room->id)->latest()->first();
        if ($request->replyTo) {
            $reply_to_object = ChatMessage::where('room_id', $room->id)->where('id', $request->replyTo)->with('user')->firstOrFail();
        }
        $user = auth()->user();
        $message = new ChatMessage();
        $message->room_id = $room->id;
        $message->user_id = $user->id;
        $message->message = $request->message;
        $message->reply_to = $request->replyTo;
        $message->save();
        if ($request->attachments) {
            $message->file_attachments = $message->associateAttachments($request->attachments);
        }

        $this->chatService->incrementUnreadCount($user->id, $room->id);
        // return ["ss"=>$user->id,"dd"=>$room->id];
        NewChatMessage::dispatch($room->id, $lastMessage->id ?? 0, [
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
                'userId' => $reply_to_object->user->user_id,
                'message' => $reply_to_object->message,
            ] : null,
            'reactions' => $message->reactionsCountByMsg(),
            'attachments' => $message->file_attachments
        ]);
        # event update sidebar chat
        $this->chatService->recentChatsSidebar($room, $user->id);
        return response(['id' => $message->id], 201);
    }

    public function getChatRoomMembers($locale, $category_slug, $chat_slug)
    {
        $category_id = Category::where('slug', $category_slug)->where('status', 'active')->firstOrFail()->id;
        $room = ChatRoom::where('category_id', $category_id)->where('slug', $chat_slug)->where('status', 'active')->firstOrFail();
        $response = $room->members()->paginate(10)->through(fn ($member) => [
            'id' => $member->user_id,
            'avatarUrl' => $member->avatar_url,
            'displayName' => $member->getDisplayName(),
            'secondaryText' => $member->email,
        ]);
        return $response;
    }

    public function newAttachment($locale, Request $request)
    {
        $createdFiles = $this->fileService->upload(
            $request,
            max_count: 3,
            max_size_mb: 10,
            types: ['jpg', 'jpeg', 'png', 'webp'],
            format_result_as_attachment: true,
            storage_key: 'community',
        );
        return response()->json($createdFiles);
    }

    public function deleteMessage($locale, $message_id)
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

    public function recentChatsSidebar()
    {
        $userId = auth()->id();
        $message = $this->chatService->getSidebar();

        RecentChatsSidebar::dispatch($userId, $message['rooms']);
        return $message;
    }

    public function getChatRoomsData($locale, Request $request)
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
        }
        $rooms =  $query->withCount('members')->paginate(12);

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
            'membersListSummary' => $room->previewMembers->take(5)->map(fn ($membership) => [
                'id' => $membership->user_id,
                'displayName' => $membership->getDisplayName(),
                'secondaryText' => $membership->email,
                'avatarUrl' => $membership->avatar_url,
            ]),
            'otherRooms' => $this->getOtherRooms($locale, $room->slug),
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
                'membersListSummary' => $room->previewMembers->take(5)->map(fn ($membership) => [
                    'id' => $membership->id,
                    'displayName' => $membership->getDisplayName(),
                    'secondaryText' => $membership->email,
                    'avatarUrl' => $membership->avatar_url,
                ]),
                'otherRooms' => $this->getOtherRooms(null, $room->slug),
                'description' => $room->description
            ]);
        $categories =  $this->categoryService->getCategoriesForFilter(new ChatRoom());

        return response()->json([
            'top_rooms' => $topViewRoom,
            'categories' => $categories,
        ]);
    }

    public function getSearchChatRooms($locale, Request $request)
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
                'membersListSummary' => $room->previewMembers->take(5)->map(fn ($membership) => [
                    'id' => $membership->user_id,
                    'displayName' => $membership->getDisplayName(),
                    'secondaryText' => $membership->email,
                    'avatarUrl' => $membership->avatar_url,
                ]),
                'otherRooms' => $this->getOtherRooms($locale, $room->slug),
                'description' => $room->description
            ]);
        if ($request->filled('search')) {
            $txt = $request->query->get('search');
            //toye title
            $rooms = $query->where(function ($q) use ($txt) {
                $q->where('title', 'like', '%' . $txt . '%');
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
                'membersListSummary' => $room->previewMembers->take(5)->map(fn ($membership) => [
                    'id' => $membership->user_id,
                    'displayName' => $membership->getDisplayName(),
                    'secondaryText' => $membership->email,
                    'avatarUrl' => $membership->avatar_url,
                ]),
                'otherRooms' => $this->getOtherRooms($locale, $room->slug),
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

    public function uploadCover($locale, ChatRoom $chat_slug, RoomCoverRequest $request)
    {
        /*if(!$this->userCanChangeRoomInfo($chat_slug)){
            return response()->json([
                'message' => 'برای تغییر در اتاق باید عضو اتاق باشید.',
            ], 400);
        }*/
        $fileService = new FileService();
        $fileUrl = $fileService->uploadOneFile(
            $request,
            storage_key: 'community',
        );
        $chat_slug->update(['banner_url' => $fileUrl['url']]);
        CoverChatRoom::dispatch($chat_slug->id, [
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

    public function join($locale, Request $request)
    {
        $user = auth()->user();
        $room = ChatRoom::where('slug', $request->chat_slug)->firstOrFail();
        $joinRequest = $this->joinRepository->findByUserRoom($user->id, $room->id);
        if ($joinRequest && $joinRequest->status == 'waiting_for_approval') {
            return response()->json([
                'message' => 'باتشکر از شکیبایی شما. بعد از تایید ادمین وارد اتاق میشوید.'
            ], 200);
        }
        if ($joinRequest && $joinRequest->status == 'reject') {
            return response()->json([
                'message' => 'درخواست شما برای وارد شدن به اتاق رد شده است.'
            ], 200);
        }
        if ($joinRequest && $joinRequest->status == 'accept') {
            return response()->json([
                'message' => 'شما وارد اتاق شده اید.'
            ], 200);
        }
        $link = url($locale . '/api/community/chat/join/' . $room->slug);
        $this->joinRepository->joinViaLink($user->id, $room->id, $link);

        return response()->json([
            'message' => 'بعداز تایید ادمین وارد اتاق " ' . $room->title . '" میشوید.'
        ], 201);
    }

    public function addMember($locale, ChatRoom $chat_slug, AddRemoveMemberRequest $request)
    {
        $member = UserProfile::where('user_id', $request->memberId)->where('status', 'active')->first();
        if (!$member) {
            return response()->json([
                'message' => 'امکان عضویت در اتاق وجود ندارد.',
            ], 404);
        }
        $chat_slug->members()->attach($member->user_id);
        $memberCount =  $chat_slug->members()->count();

        AddChatroomMember::dispatch($chat_slug->id, [
            'id' => $member->user_id,
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
                'id' => $member->user_id,
                'displayName' => $member->getDisplayName(),
                'avatarUrl' => $member->avatar_url,
            ],
        ]);
    }

    public function removeRoomMember($locale, Category $category_slug, ChatRoom $chat_slug, AddRemoveMemberRequest $request)
    {
        /*if(!$this->userCanChangeRoomInfo($chat_slug)){
            return response()->json([
                'message' => 'برای تغییر در اتاق باید عضو اتاق باشید.',
            ], 400);
        }*/
        $member = UserProfile::where('user_id', $request->memberId)->firstOrFail();
        $chat_slug->members()->detach($member->user_id);
        $memberCount =  $chat_slug->members()->count();
        RemoveChatroomMember::dispatch($chat_slug->id, [
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

    public function editRoomDescription($locale, Category $category_slug, ChatRoom $room, EditDescriptionRequest $request)
    {
        /*if(!$this->userCanChangeRoomInfo($room)){
            return response()->json([
                'message' => 'برای تغییر در اتاق باید عضو اتاق باشید.',
            ], 400);
        }*/
        $room->update(['description' => $request->description]);

        $data = [
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
            'membersListSummary' => $room->previewMembers->take(5)->map(fn ($membership) => [
                'id' => $membership->user_id,
                'displayName' => $membership->getDisplayName(),
                'secondaryText' => $membership->email,
                'avatarUrl' => $membership->avatar_url,
            ]),
            'otherRooms' => $this->getOtherRooms($locale, $room->slug),
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

    public function getChatRoomsPannelData($locale, Request $request)
    {
        $query = ChatRoom::with(['previewMembers', 'category'])->withCount('members');

        $sortOrder = 'desc';
        if (isset($request->sortOrder) && ($request->sortOrder ==  'asc' || $request->sortOrder ==  'desc')) {
            $sortOrder = $request->sortOrder;
        }

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

        $rooms =  $query->orderBy('id', 'desc')->paginate(10);
        return new ChatRoomsResource($rooms);
    }

    public function updateRoomStatus($locale, UpdateRoomStatusRequest $request)
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
        $users = UserProfile::where('status', 'active')->get()
            ->map(fn ($user) => [
                'id' => $user->user_id,
                'displayName' => $user->getDisplayName(),
                'avatarUrl' => $user->avatar_url,
            ]);
        return response()->json([
            'categories' => $categories,
            'users' => $users,
        ]);
    }

    public function createUpdateChatRoomsPannel(CreateRoomRequest $request)
    {
        if (!isset($request['mostPopular'])) {
            $request['mostPopular'] = 0;
        }

        $room = ChatRoom::updateOrCreate(
            ['id' => $request['id']],
            [
                'category_id' => $request['categoryId'],
                'course_id' => $request['courseId'],
                'title' => $request['title'],
                'slug' => $request['slug'] ? $request['slug'] : SlugGenerator::transform($request['title']),
                'is_private' => $request['isPrivate'],
                'max_member' => $request['maxMember'],
                'banner_url' => $request['bannerUrl'],
                'avatar_url' => $request['avatarUrl'],
                'description' => $request['description'],
                'most_popular' => $request['mostPopular']
            ]
        );

        if ($request->filled('members')) {
            $room->members()->sync($request->members);
        }

        return $room->id;
    }

    public function deleteRoom($locale, $id)
    {
        $room = ChatRoom::query()->findOrFail($id);
        $room->members()->detach();
        $room->delete();

        return response()->json([
            'data' => [],
            'status' => 200,
            'message' => 'با موفقیت انجام شد'
        ], 200);
    }

    public function getChatData($locale, $id)
    {
        $room = ChatRoom::with(['category', 'members'])->where('id', $id)->firstOrFail();
        return new ChatRoomResource($room);
    }

    public function updateChat($locale, UpdateRoomRequest $request, ChatRoom $room)
    {
        $data = $request->only(ChatRoom::getModel()->fillable);

        if ($request->file('file')) {
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
        $user = auth()->user();

        $chatRoomIds = ChatRoomMembership::where('user_id', $user->id)->pluck('chat_room_id');

        $myRoom = ChatRoom::whereIn('id', $chatRoomIds)->paginate();

        return  new ChatRoomsResource($myRoom);
    }

    public function userCanChangeRoomInfo($locale, $room)
    {
        $user = auth()->user();
        $checkUser = $room->members()->pluck('community_chat_room_memberships.user_id')->toArray();
        if (!$user) {
            return response()->json([
                'message' => 'ابتدا وارد شوید.',
            ], 401);
        }
        if (!in_array($user->id, $checkUser)) {
            return false;
        }
        return true;
    }
}

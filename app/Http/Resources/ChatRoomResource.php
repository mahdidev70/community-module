<?php

namespace TechStudio\Community\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatRoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->discription,
            'isPrivate' => $this->is_private,
            'status' => $this->status,
            'avatarUrl' => $this->avatar_url,
            'bannerUrl' => $this->banner_url,
            'creationDate' => $this->created_at,
            'maxMember' => $this-> max_member,
            'memberCount' => $this->members->count(),
            'joinLink' => route('join.chatroom').'/'.$this->slug,
            'category' => [
                'id' => $this->category->id,
                'title' => $this->category->title,
                'slug' => $this->category->slug,
            ],
            'members' => ChatRoomMemberResource::collection($this->members),
        ];
    }
}

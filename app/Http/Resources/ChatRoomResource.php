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
            'description' => $this->description,
            'isPrivate' => $this->is_private,
            'status' => $this->status,
            'avatarUrl' => $this->avatar_url,
            'bannerUrl' => $this->banner_url,
            'creationDate' => $this->created_at,
            'maxMember' => $this-> max_member,
            'memberCount' => $this->members->count(),
            'joinLink' => $this->join_link /*route('join.chatroom').'/'.$this->slug*/,
            'categoryId' => $this->category->id ?? null,
            'category' => [
                'id' => $this->category->id ?? null,
                'title' => $this->category->title ?? null,
                'slug' => $this->category->slug ?? null,
            ],
            'members' => ChatRoomMemberResource::collection($this->members),
            'mostPopular' => $this->most_popular,
        ];
    }
}

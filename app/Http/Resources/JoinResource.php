<?php

namespace TechStudio\Community\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JoinResource extends JsonResource
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
            'room' => new ChatRoomResource($this->room),
            'user' => [
                'id' => $this->user->user_id,
                'displayName' => $this->user->getDisplayName(),
                'avatarUrl' => $this->user->avatar_url,
            ],
            'status' => $this->status,
            'createdAt' => $this->created_at
            ];
    }
}

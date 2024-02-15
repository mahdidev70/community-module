<?php

namespace TechStudio\Community\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatRoomMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->user_id,
            'displayName' => $this->getDisplayName(), 
            'avatarUrl' => $this->avatar_url,
        ];
    }
}

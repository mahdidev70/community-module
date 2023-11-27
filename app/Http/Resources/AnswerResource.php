<?php

namespace TechStudio\Community\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnswerResource extends JsonResource
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
            'question' => [
                'id' => $this->question->id,
                'title' => $this->question->text,
                'slug' =>$this->question->slug,
            ],
            'author' => [
                'id' => $this->user->id, 
                'displayName' => $this->user->getDisplayName(), 
                'avatarUrl' => $this->user->avatar_url, 
            ],
            'title' => $this->text,
            'status' => $this->status,
            'creationDate' => $this->created_at,
            'likesCount' => $this->likes_count ?? 0,
            'dislikesCount' => $this->dislikes_count ?? 0,
        ];
    }
}

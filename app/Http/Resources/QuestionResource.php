<?php

namespace TechStudio\Community\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
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
            'text' => $this->text,
            'slug' => $this->slug,
            'creationDate' =>$this->created_at,
            'category' => [
                'title' => $this->category->title,
                'slug' => $this->category->slug,
            ],
            'author' => [
                'id' => $this->asker->id,
                'displayName' => $this->asker->getDisplayName(),
                'avatarUrl' => $this->asker->avatar_url,
            ],
            'likesCount' => $this->likes_count ?? 0,
            'dislikesCount' => $this->dislikes_count ?? 0,
            'answersCount' => $this-> allAnswers->count(),
            'attachments' => $this->attachments,
            'publicationDate' => $this->publication_date,
            'viewsCount' => $this->viewsCount,
            'status' => $this->status,
        ];
    }
}

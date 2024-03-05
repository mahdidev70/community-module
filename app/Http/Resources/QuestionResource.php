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
            'qText' => $this->text,
            'slug' => $this->slug,
            'creationDate' =>$this->created_at,
            'category' => [
                'id' => $this->category->id ?? '',
                'title' => $this->category->title ?? '',
                'slug' => $this->category->slug ?? '',
            ],
            'author' => [
                'id' => $this->asker->user_id,
                'displayName' => $this->asker->getDisplayName(),
                'avatarUrl' => $this->asker->avatar_url,
            ],
            'likesCount' => $this->likes_count ?? 0,
            'dislikesCount' => $this->dislikes_count ?? 0,
            'answersCount' => $this-> allAnswers->count(),
            'attachments' => [
                'id' => $this->attachments->id ?? null,
                'type' => 'image',
                'contentUrl' => $this->attachments->file_url ?? null,
                'previewImageUrl' => $this->attachments->file_url ?? null,
                'creationDate' => $this->attachments->created_at ?? null,
            ],
            'publicationDate' => $this->publication_date,
            'viewsCount' => $this->viewsCount,
            'status' => $this->status,
        ];
    }
}

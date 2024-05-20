<?php

namespace TechStudio\Community\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionOldResource extends JsonResource
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
            'slug' => $this->slug,
            'text' => $this->text,
            'status' => $this->status,
            'creationDate' => $this->created_at,
            'viewsCount' => $this->viewsCount,
            'category' => [
                'slug' => $this->category?$this->category->slug:null,
                'title' => $this->category?$this->category->title:null,
            ],
            'asker' => [
                'displayName' => $this->asker->getDisplayName(),
                'avatarUrl' => $this->asker->avatar_url,
                'id' => $this->asker->id,
                'tag' => $this->asker->getTag(),
            ],
            'feedback' => [
                'likesCount' => $this->likes_count,
                'dislikesCount' => $this->dislikes_count,
                'currentUserAction' => $this->current_user_feedback(),
            ],
            'topAnswers' => $this->topAnswers->map( fn($answer) => [
                'id' => $answer->user->id,
                'displayName' => $answer->user->getDisplayName(),
                'avatarUrl' => $answer->user->avatar_url,
            ]),
            'answersCount' => $this->answers_count,
            'attachments' => $this->attachments->map(fn ($file) => [
                'id' => $file->id,
                'type' => 'image',  // TODO: infer
                'previewImageUrl' => $file->file_url,
                'contentUrl' => $file->file_url,
            ]),
        ];
    }
}

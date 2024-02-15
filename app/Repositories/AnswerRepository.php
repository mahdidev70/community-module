<?php

namespace TechStudio\Community\app\Repositories;

use Illuminate\Support\Facades\Auth;
use TechStudio\Community\app\Models\Answer;
use TechStudio\Community\app\Repositories\Interfaces\AnswerRepositoryInterface;

class AnswerRepository implements AnswerRepositoryInterface
{
    public function getAnswersList($data)
    {
        $query = Answer::with(['question']);

        if ($data->filled('search')) {
            $txt = $data->get('search');

            $query->where(function ($q) use ($txt) {
                $q->Where('text', 'like', '% '.$txt.'%');
            });
        }

        if ($data->has('sort')) {
            if ($data->sort == 'creationDate') {
                $query->orderByDesc('created_at');
            } elseif ($data->sort == 'dislikesCount') {
                $query->withCount('dislikes')->orderByDesc('dislikes_count');
            } elseif ($data->sort == 'likesCount') {
                $query->withCount('likes')->orderByDesc('likes_count');
            }
        }else {
            $query = $query->orderBy('id', 'desc');
        }

        if (isset($data->questionSlug) && $data->questionSlug != null) {
            $query->whereHas('question', function ($questionQuery) use ($data) {
                $questionQuery->where('slug', $data->input('questionSlug'));
            });
        }
        if (isset($data->creationDateMax) && $data->creationDateMax != null) {
            $query->whereDate('created_at', '<=', $data->input('creationDateMax'));
        }
        if (isset($data->creationDateMin) && $data->creationDateMin != null) {
            $query->whereDate('created_at', '>=', $data->input('creationDateMin'));
        }
        if (isset($data->status) && $data->status != null) {
            $query->where('status', $data->input('status'));
        }

        $answers = $query->paginate(10);
        return $answers;
    }

    public function createUpdate($data) 
    {
        $user = auth()->user();

        $answer = Answer::updateOrCreate(
            ['id' => $data['id']],
            [
                'question_id' => $data['questionId'],
                'user_id' => $user->id,
                'text' => $data['text'],
                'attachments' => $data['attachments'],
            ]
        );

        return $answer;
    }
}

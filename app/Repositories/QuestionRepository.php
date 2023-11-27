<?php

namespace TechStudio\Community\app\Repositories;

use TechStudio\Community\app\Models\Question;
use TechStudio\Community\app\Repositories\Interfaces\QuestionRepositoryInterface;

class QuestionRepository implements QuestionRepositoryInterface
{
    public function getQuestionList($data)
    {
        $query = Question::with(['asker', 'category', 'allAnswers']);

        if ($data->filled('search')) {
            $txt = $data->get('search');

            $query->where(function ($q) use ($txt) {
                $q->Where('text', 'like', '% '.$txt.'%');
            });
        }

        if ($data->has('sort')) {

            if ($data->sort == 'answersCount') {
                $query->withCount('allAnswers')->orderByDesc('all_answers_count');
            } elseif ($data->sort == 'dislikesCount') {
                $query->withCount('dislikes')->orderByDesc('dislikes_count');
            } elseif ($data->sort == 'likesCount') {
                $query->withCount('likes')->orderByDesc('likes_count');
            }elseif ($data->sort == 'publicationDate') {
                $query->orderByDesc('publication_date');
            }

        }

        if (isset($data->category) && $data->category != null) {
            $query->whereHas('category', function ($categoryQuery) use ($data) {
                $categoryQuery->where('slug', $data->input('category'));
            });
        }

        if (isset($data->publicationDateMin) && $data->publicationDateMin != null) {
            $query->whereDate('publication_date', '>=', $data->input('publicationDateMin'));
        }

        if (isset($data->publicationDateMax) && $data->publicationDateMax != null) {
            $query->whereDate('publication_date', '<=', $data->input('publicationDateMax'));
        }

        if (isset($data->status) && $data->status != null) {
            $query->where('status', $data->input('status'));
        }

        $questions = $query->paginate(10);

        return $questions;
    }
}

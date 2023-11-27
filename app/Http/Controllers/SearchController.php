<?php

namespace TechStudio\Community\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use TechStudio\Community\app\Models\Question;

class SearchController extends Controller
{
    public function searchQuestion(Request $request)
    {

        $res = [];
        if($request->filled('query')){
            $txt = $request->query->get('query');
            //toye content
            $questions = Question::where('status','approved')->with(['topAnswers', 'category'])
                ->withCount('answers')->where(function($q) use($txt){
                $q->where('text','like', '%'.$txt)->orWhere('text', 'like', '% '.$txt.'%')->orWhere('text','like',$txt.'%');
            });
            if ($request->filled('category')) {
                $categorySlug = $request->category;
                $questions->whereHas('category', function ($query) use ($categorySlug) {
                    $query->where('slug', $categorySlug);
                });
            }
            $questions=  $questions->take(10)->get(['text','slug']);
         //   return $questions[0]->top_answers;
            $res = $questions->map(fn($question)=>[
                "id" => $question->id,
                "text" => $question->text,
                "slug" => $question->slug,
                "category" => [
                    "id" => $question->category->id,
                    "title" => $question->category->title,
                    "slug" => $question->category->slug,
                    ],
                'feedback' => [
                    'likesCount' => $question->likes_count??0,
                    'dislikesCount' => $question->dislikes_count??0,
                    'currentUserAction' => $question->current_user_feedback(),
                ],
                'answersUsers' => $question->topAnswers->map(fn($answer)=>[
                        'id' => $answer->user->id,
                        'displayName' => $answer->user->getDisplayName(),
                        'avatarUrl' =>  $answer->user->avatar_url,
                    ]),
            ]);

        }

        return $res;
     }

}

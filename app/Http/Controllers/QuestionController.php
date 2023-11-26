<?php

namespace App\Http\Controllers\Community\Question;

use App\Helper\SlugGenerator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Community\Question\CreateQuestionRequest;
use App\Http\Requests\Community\Question\ReactRequest;
use App\Http\Requests\Community\Question\UpdateQuestionStatusRequest;
use App\Http\Resources\Community\QuestionsResource;
use App\Models\Answer;
use App\Models\Category;
use App\Models\Question;
use App\Repositories\Community\Interfaces\QuestionRepositoryInterface;
use App\Services\File\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class QuestionController extends Controller
{
    private QuestionRepositoryInterface $questionRepository;

    public function __construct(
        protected FileService $fileService,
        QuestionRepositoryInterface $questionRepository,
    )
    {
        $this->questionRepository = $questionRepository;
    }

    private function formatQuestion($question)
    {
        $data = [
            "text" => $question->text,
            "creationDate" => $question->created_at,
            "category" => [
                "title" => $question->category->title,
                "slug" => $question->category->slug
            ],
            'asker' => [
                'id' => $question->asker->id,
                'tag' => $question->asker->getTag(),
                'displayName' => $question->asker->getDisplayName(),
                'avatarUrl' => $question->asker->avatar_url,
            ],
            'feedback' => [
                'likesCount' => $question->likes_count ?? 0,
                'dislikesCount' => $question->dislikes_count ?? 0,
                'currentUserAction' => $question->current_user_feedback(),
            ],
            'attachments' => $question->attachments->map(function ($file) {
                return [
                    'id' => $file->id,
                    'type' => 'image',
                    'previewImageUrl' => $file->file_url,
                    'contentUrl' => $file->file_url,
                ];
            }),
            
        ];

        $relevantQuestions = Question::where('category_id',$question->category_id)
            ->latest()
            ->take(5)
            ->select('slug', 'text AS title')
            ->get();

        $answers = $question->answers()
            ->with('user')
            ->latest()
            ->get()
            ->map(function($answer) {
                return $this->formatAnswer($answer);
            });

        return [
            "question" => $data,
            "answers" => $answers,
            "relevantQuestions" => $relevantQuestions
        ];
    }

    private function formatAnswer($answer)
    {
        return [
            'id' => $answer->id,
            'user' => [
                'displayName' => $answer->user->getDisplayName(),
                'avatarUrl' => $answer->user->avatar_url,
            ],
            'creationDate' => $answer->created_at,
            'feedback' => [
                'likesCount' => $answer->likes_count ?? 0,
                'dislikesCount' => $answer->dislikes_count ?? 0,
                'currentUserAction' => $answer->current_user_feedback(),
            ],
            'text' => $answer->text,
            'status' => $answer->status,
            'answerId' => $answer->id,
            'questionId' => $answer->question_id,
            'attachments' => $answer->attachments->map(function ($file) {
                return [
                    'id' => $file->id,
                    'type' => 'image',
                    'previewImageUrl' => $file->file_url,
                    'contentUrl' => $file->file_url,
                ];
            }),
        ];
    }

    public function getHomepageQuestionsData(Request $request) {
        $questions = Question::where('status', 'approved')->with(['asker', 'category', 'attachments', 'topAnswers'])
            ->withCount('answers')->withCount('likes');
        if (!$request->has('sort')) {
            $questions->orderBy('created_at', 'ASC');
        }else{
            if ($request->sort == 'recent') {
                $questions->orderBy('created_at', 'DESC');
            } else if ($request->sort == 'views') {
                $questions->orderBy('viewsCount', 'DESC');
            } else if ($request->sort == 'likes') {
                $questions->rightJoin('likes', function ($join) {
                    $join->on('questions.id', '=', 'likes.likeable_id')
                        ->where('likes.likeable_type', '=', 'App\Models\Question');
                })->select('questions.*', DB::raw('COUNT(likes.action) as like_count'))
                    ->where('likes.action', '=', 'like')
                    ->groupBy('questions.id')
                    ->orderByDesc('like_count');
            } else if($request->sort == 'noneAnswer'){

                $questions->whereHas('answers', null, '<',1)
                ->orderBy('created_at', 'desc');
            } else {
                return response()->json(
                    ['message' => "Unexpected sorting parameter. Use 'recent', 'views' or 'likes'."], 400
                );
            }
        }

        if ($request->has('category') && strlen($request->category) > 0){
            $questions->whereHas('category',function ($query) use($request){
                $query->whereIn('slug', explode(',', $request->category));
            });
        }


            $questions = $questions->take(10)->paginate(5)->through(
            fn ($q) => [
                'id' => $q->id,
                'slug' => $q->slug,
                'text' => $q->text,
                'status' => $q->status,
                'creationDate' => $q->created_at,
                'category' => [
                    'slug' => $q->category->slug,
                    'title' => $q->category->title,
                ],
                'asker' => [
                    'displayName' => $q->asker->getDisplayName(),
                    'avatarUrl' => $q->asker->avatar_url,
                    'id' => $q->asker->id,
                    'tag' => $q->asker->getTag(),
                ],
                'feedback' => [
                    'likesCount' => $q->likes_count,
                    'dislikesCount' => $q->dislikes_count,
                    'currentUserAction' => $q->current_user_feedback(),
                ],
                'topAnswers' => $q->topAnswers->map( fn($answer) => [
                    'id' => $answer->user->id,
                    'displayName' => $answer->user->getDisplayName(),
                    'avatarUrl' => $answer->user->avatar_url,
                ]),
                'answersCount' => $q->answers_count,
                'attachments' => $q->attachments->map(fn ($file) => [
                    'id' => $file->id,
                    'type' => 'image',  // TODO: infer
                    'previewImageUrl' => $file->file_url,
                    'contentUrl' => $file->file_url,
                ]),
            ]
        );
        return $questions;
    }

    public function storeFeedbackToQuestion($question_slug, ReactRequest $request) {
        $question_query = Question::where('slug', $question_slug)->where('status', 'approved')->firstOrFail();
        $currentUserAction = $request->action;
            // likeBy() or dislikeBy() or clearBy
         $functionName = strtolower($request->action).'By';
          $question_query->$functionName(Auth::user()->id);
          return [
              'feedback' => [
                  'likesCount' => $question_query->likes_count??0,
                  'dislikesCount' => $question_query->dislikes_count??0,
                  'currentUserAction' => $currentUserAction,
              ],
          ];
    }

    public function newQuestion(CreateQuestionRequest $request) {
        $category = Category::where('slug', $request->categorySlug)->firstOrFail();
        $user = Auth::user();

        $question = new Question();
        $question->slug = $request->slug;
        $question->asker_user_id = $user->id;
        $question->category_id = $category->id;
        $question->text = $request->text;
       /* $question->status = 'waiting_for_approval';  // FIXME in production*/
        $question->save();
        if ($request->attachments) {
            $question['attachments'] = $question->associateAttachments($request->attachments);
        }
        $question['category'] = ["slug"=>$category->slug,"title"=>$category->title];
        $question['user'] = [
            'displayName' => $user->getDisplayName(),
            'avatarUrl' => $user->avatar_url,
            'id' => $user->id
        ];
        $question['creationDate'] = $question['created_at'];
        unset($question['created_at']);
        return response(
                $question, 201);
    }

    public function singleQuestionData($question_slug, Request $request)
    {
        $question = Question::where('slug', $question_slug)->where('status', 'approved')->with(['asker', 'category'])->firstOrFail();
        $question->increment('viewsCount');
        $data = $this->formatQuestion($question);
        $relevantQuestions = Question::where('category_id', $question->category_id)
            ->where('status', 'approved')
            ->latest()
            ->take(5)
            ->select('slug', 'text AS title')
            ->get();

        $answers = $question->answers()
            ->with('user')
            ->latest()
            ->get()
            ->map(function($answer) {
                return $this->formatAnswer($answer);
            })
            ->toArray();

        $sort_function = null;
        if ($request->query('sortBy', 'recent') == 'recent') {
        } else if ($request->query('sortBy') == 'likes') {
            $sort_function = function($answer_a, $answer_b) {
                $a_likes = $answer_a['feedback']['likesCount'];
                $a_dislikes = $answer_a['feedback']['dislikesCount'];
                $a_popularity = $a_likes - $a_dislikes;
                $b_likes = $answer_b['feedback']['likesCount'];
                $b_dislikes = $answer_b['feedback']['dislikesCount'];
                $b_popularity = $b_likes - $b_dislikes;
                return $a_popularity < $b_popularity;
            };
            usort($answers, $sort_function);
        } else {
            throw new BadRequestException("'sortBy' query param must be either 'recent' or 'likes'");
        }

        return [
            "question" => $data['question'],
            "answers" => $answers,
            "relevantQuestions" => $relevantQuestions
        ];
    }

    public function newAttachment(Request $request) {
        $createdFiles = $this->fileService->upload(
            $request,
            max_count: 3,
            max_size_mb: 10,
            types: ['jpg', 'jpeg', 'png'],
            format_result_as_attachment: true,
            storage_key: 'community',
        );
        return response()->json($createdFiles);

    }


    public function getQuestionList(Request $request)
    {
        $questions = $this->questionRepository->getQuestionList($request);

        return new QuestionsResource($questions);
    }

    public function getQuestionListCommon() 
    {
        $quesitonModel = new Question();

        $counts = [
            'all' => $quesitonModel->count(),
            'approved' => $quesitonModel->where('status', 'approved')->count(),
            'hidden' => $quesitonModel->where('status', 'hidden')->count(),
            'waiting_for_approval' => $quesitonModel->where('status', 'waiting_for_approval')->count(),
        ];

        $categories = Category::where('table_type', get_class($quesitonModel))->get()->map(function ($category) {
            return [
                'title' => $category->title,
                'slug' => $category->slug,
            ];
        });

        $status = [
            'approved', 'hidden', 'waiting_for_approval'
        ];

        return [
            'counts' => $counts,
            'categories' => $categories,
            'status' => $status,
        ];
    }


    public function updateQuestionStatus(UpdateQuestionStatusRequest $request)
    {
        Question::whereIn('id', $request['ids'])
            ->update(['status' => $request['status']]);
        return [
            'updatedQuestions' => $request['ids'],
        ];
    }

}

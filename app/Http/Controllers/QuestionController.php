<?php

namespace TechStudio\Community\app\Http\Controllers;

use Illuminate\Http\Request;
use App\Helper\SlugGenerator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use TechStudio\Core\app\Models\Category;
use TechStudio\Community\app\Models\Answer;
use TechStudio\Community\app\Models\Question;
use TechStudio\Core\app\Services\File\FileService;
use TechStudio\Community\app\Http\Requests\ReactRequest;
use TechStudio\Community\app\Http\Resources\AnswerResource;
use TechStudio\Community\app\Http\Resources\AnswersResource;
use TechStudio\Community\app\Http\Resources\QuestionResource;
use TechStudio\Community\app\Http\Resources\QuestionsResource;
use TechStudio\Community\app\Http\Requests\CreateQuestionRequest;
use TechStudio\Community\app\Http\Resources\QuestionsOldResource;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use TechStudio\Community\app\Http\Requests\UpdateQuestionStatusRequest;
use TechStudio\Community\app\Repositories\Interfaces\QuestionRepositoryInterface;

class QuestionController extends Controller
{
    private QuestionRepositoryInterface $questionRepository;

    public function __construct(
        protected FileService       $fileService,
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
                "title" => $question->category ? $question->category->title : null,
                "slug" => $question->category ? $question->category->slug : null
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
            'viewsCount' => $question->viewsCount
        ];

        $relevantQuestions = Question::where('category_id', $question->category_id)
            ->latest()
            ->take(5)
            ->select('slug', 'text AS title')
            ->get();

        $answers = $question->answers()
            ->with('user')
            ->latest()
            ->get()
            ->map(function ($answer) {
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

    public function getHomepageQuestionsData(Request $request)
    {
        $questions = Question::where('status', 'approved');
        if (auth()->guard('api')->user()) {
            $questions->orWhere(function ($query) {
                $query->where('status', 'waiting_for_approval')
                    ->where('asker_user_id', auth()->id());
            });
        }
        $questions->with(['asker', 'category', 'attachments', 'topAnswers'])
            ->withCount('answers')->withCount('likes');
        if (!$request->has('sort')) {
            $questions->orderBy('created_at', 'DESC');
        } else {
            if ($request->sort == 'recent') {
                $questions->orderBy('created_at', 'DESC');
            } else if ($request->sort == 'views') {
                $questions->orderBy('viewsCount', 'DESC');
            } else if ($request->sort == 'likes') {
                $questions->rightJoin('core_likes', function ($join) {
                    $join->on('community_questions.id', '=', 'core_likes.likeable_id')
                        ->where('core_likes.likeable_type', '=', 'TechStudio\Community\app\Models\Question');
                })->select('community_questions.*', DB::raw('COUNT(core_likes.action) as like_count'))
                    ->where('core_likes.action', '=', 'like')
                    ->groupBy('community_questions.id')
                    ->orderByDesc('like_count');
            } else if ($request->sort == 'noneAnswer') {

                $questions->whereHas('answers', null, '<', 1)
                    ->orderBy('created_at', 'desc');
            } else {
                return response()->json(
                    ['message' => "Unexpected sorting parameter. Use 'recent', 'views' or 'likes'."], 400
                );
            }
        }

        if ($request->has('category') && strlen($request->category) > 0) {
            $questions->whereHas('category', function ($query) use ($request) {
                $query->whereIn('slug', explode(',', $request->category));
            });
        }


        $questions = $questions->take(10)->paginate(5);
        return new QuestionsOldResource($questions);
    }

    public function storeFeedbackToQuestion($local, $question_slug, ReactRequest $request)
    {
        $question_query = Question::where('slug', $question_slug)->where('status', 'approved')->first();
        if (!$question_query) {
            return response()->json([
                'message' => 'مجاز به دادن لایک/دیسلایک به این سوال نیستید.',
            ], 400);
        }
        if (auth()->id() == $question_query->asker_user_id) {
            return response()->json([
                'message' => 'مجاز به دادن لایک/دیسلایک به این سوال نیستید.',
            ], 400);
        }
        $currentUserAction = $request->action;
        // likeBy() or dislikeBy() or clearBy
        $functionName = strtolower($request->action) . 'By';
        $question_query->$functionName(auth()->id());
        return [
            'feedback' => [
                'likesCount' => $question_query->getLikes()->count() ?? 0,
                'dislikesCount' => $question_query->getDislikes()->count() ?? 0,
                'currentUserAction' => $currentUserAction,
            ],
        ];
    }

    public function newQuestion(CreateQuestionRequest $request)
    {
        $category = Category::where('slug', $request->categorySlug)->firstOrFail();
        $user = auth()->user();

        $question = new Question();
        $question->slug = $request->slug;
        $question->asker_user_id = $user->id;
        $question->category_id = $category->id;
        $question->text = $request->text;
        $question->status = 'waiting_for_approval';
        $question->save();
        if ($request->attachments) {
            $question['attachments'] = $question->associateAttachments($request->attachments);
        }
        $question['category'] = ["slug" => $category->slug, "title" => $category->title];
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

    public function singleQuestionData($local, $question_slug, Request $request)
    {
        $question = Question::where('slug', $question_slug)->with(['asker', 'category'])->firstOrFail();
        $question->update([
            "viewsCount" => $question->viewsCount ? $question->viewsCount + 1 : 1
        ]);
        /*return $question->increment('viewsCount');*/
        if ($question->status != 'approved' && auth()->id() != $question->asker_user_id) {
            return response()->json(
                ['message' => "باید ابتدا سوال تایید گردد."], 400
            );
        }
        $data = $this->formatQuestion($question);

        $relevantQuestions = Question::where('category_id', $question->category_id)
            ->where('status', 'approved')
            ->latest('created_at')
            ->take(5)
            ->select('slug', 'text AS title')
            ->get();

        $questionAnswers = $question->answers()
            ->with('user')
            ->latest('created_at')
            ->get()
            ->map(function ($answer) {
                return $this->formatAnswer($answer);
            })
            ->toArray();

        $userAnswers = [];
        if (auth()->user()) {
            $userAnswers = $question->allAnswers()
                ->where(function ($query) {
                    $query->where('user_id', auth()->id())->where('status', 'waiting_for_approval');
                })
                ->with('user')
                ->latest('created_at')
                ->get()
                ->map(function ($answer) {
                    return $this->formatAnswer($answer);
                })
                ->toArray();
        }
        $answers = array_merge($userAnswers, $questionAnswers);
        $sort_function = null;
        if ($request->query('sortBy', 'recent') == 'recent') {
        } else if ($request->query('sortBy') == 'likes') {
            $sort_function = function ($answer_a, $answer_b) {
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

    public function newAttachment(Request $request)
    {
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
                'id' => $category->id,
                'title' => $category->title,
                'slug' => $category->slug,
            ];
        });

        $status = [
            'approved', 'hidden', 'waiting_for_approval', 'deleted',
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

    public function createUpdateQuestion(Request $request)
    {
        $question = $this->questionRepository->createUpdate($request);
        return new QuestionResource($question);
    }

    public function getUserQuestion(Request $request)
    {
        $user = auth()->user();

        if ($request['data'] == 'my') {

            $myQuestions = Question::where('asker_user_id', $user->id)->paginate(10);
            return new QuestionsResource($myQuestions);

        } elseif ($request['data'] == 'their') {

            $myAnswer = Answer::where('user_id', $user->id)->paginate(10);
            return new AnswersResource($myAnswer);

        }

    }
}

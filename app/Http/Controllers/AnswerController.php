<?php

namespace TechStudio\Community\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use TechStudio\Community\app\Http\Requests\AnswerRequest;
use TechStudio\Community\app\Http\Requests\ReactRequest;
use TechStudio\Community\app\Http\Requests\UpdateAnswerStatusRequest;
use TechStudio\Community\app\Http\Resources\AnswerResource;
use TechStudio\Community\app\Http\Resources\AnswersResource;
use TechStudio\Community\app\Models\Answer;
use TechStudio\Community\app\Models\Question;
use TechStudio\Community\app\Repositories\Interfaces\AnswerRepositoryInterface;
use TechStudio\Core\app\Services\File\FileService;

class AnswerController extends Controller
{

    private AnswerRepositoryInterface $answerRepository;

    public function __construct(
        protected FileService $fileService,
        AnswerRepositoryInterface $answerRepository,
    )
    {
        $this->answerRepository = $answerRepository;
    }

    public function newAnswer($local, Question $slug,AnswerRequest $request)
    {
        if ($slug->status != 'approved'){
            return response()->json([
                'message' => 'Cannot answer to this message',
            ], 400);
        }
        $data = $request->only(Answer::getModel()->fillable);
        $data['user_id'] = Auth::user()->id;
        $data['question_id']= $slug->id;
        $answer = Answer::create($data);
        if ($request->attachments) {
          $answer->associateAttachments($request->attachments);
          $answer['attachments'] = $answer->attachments()->get(['file_url as previewImageUrl','id as id']);
        }
        $data = [
            "text" => $answer->text,
            "creationDate" => $answer->created_at,
            "category" => [
                "title" => $slug->category->title,
                "slug" => $slug->category->slug
            ],
            'asker' => [
                'id' => $answer->user->id,
                'displayName' => $answer->user->getDisplayName(),
                'avatarUrl' => $answer->user->avatar_url,
            ],
            'feedback' => [
                'likesCount' => $answer->likes_count??0,
                'dislikesCount' => $answer->dislikes_count??0,
                'currentUserAction' => $answer->current_user_feedback(),
            ],
            'attachments' => $answer['attachments']
        ];
        return response($data, 201);
    }

    public function storeFeedbackToAnswer($local, $question_slug,$answer_id,ReactRequest $request)
    {
        $answer = Answer::where('id', $answer_id)->firstOrFail();
        if ($answer->question() && $answer->question->slug != $question_slug){
            return response()->json([
                'message' => 'Cannot feedback to this message',
            ], 400);
        }
        $currentUserAction = $request->action;
        $functionName = strtolower($request->action).'By';
        $answer->$functionName(Auth::user()->id);
        return [
            'feedback' => [
                'likesCount' => $answer->likes_count??0,
                'dislikesCount' => $answer->dislikes_count??0,
                'currentUserAction' => $currentUserAction,
            ],
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

    public function updateAnswerStatus(UpdateAnswerStatusRequest $request)
    {
        Answer::whereIn('id', $request['ids'])
            ->update(['status' => $request['status']]);
        return [
            'updatedAnswers' => $request['ids'],
        ];
    }

    public function getAnswerList(Request $request)
    {
        $answers = $this->answerRepository->getAnswersList($request);

        return new AnswersResource($answers);
    }

    public function getAnswerListCommon() 
    {
        $counts = [
            'all' => Answer::count(),
            'approved' => Answer::where('status', 'approved')->count(),
            'hidden' => Answer::where('status', 'hidden')->count(),
            'waiting_for_approval' => Answer::where('status', 'waiting_for_approval')->count(),
        ];

        $questions = Answer::with('question')->get()->map(function ($answer){
            return [
                'id' => $answer->question->id,
                'slug' => $answer->question->slug,
            ];
        });


        $status = [
            'approved', 'hidden', 'waiting_for_approval'
        ];

        return [
            'counts' => $counts,
            'status' => $status,
            'question' => $questions,
        ];
    }

    public function createUpdateAnswer(Request $request)
    {
        $answer = $this->answerRepository->createUpdate($request);
        return new AnswerResource($answer);
    }
}

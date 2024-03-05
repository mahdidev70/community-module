<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use TechStudio\Community\app\Http\Controllers\AnswerController;
use TechStudio\Community\app\Http\Controllers\ChatMessageReactController;
use TechStudio\Community\app\Http\Controllers\ChatRoomController;
use TechStudio\Community\app\Http\Controllers\CommunityHomePageController;
use TechStudio\Community\app\Http\Controllers\JoinController;
use TechStudio\Community\app\Http\Controllers\QuestionController;
use TechStudio\Community\app\Http\Controllers\SearchController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/question/search', [SearchController::class, 'searchQuestion']);

Route::get('community/chat/{category_slug}/{chat_slug}/common', [ChatRoomController::class, 'getSingleChatPageCommonData']);
Route::get('community/chat/{category_slug}/{chat_slug}/data/preview', [ChatRoomController::class, 'getPreviewSingleChatPageMessages']);
Route::get('community/chat/recentChatsSidebar/preview', [ChatRoomController::class, 'previewRecentChatsSidebar']);

Route::prefix('community')->group(function() {
    Route::middleware("auth:sanctum")->group(function () {
        Route::prefix('chat')->group(function () {

            Route::get('rooms/common', [ChatRoomController::class, 'getChatRoomsCommon']); // Done
            Route::get('rooms/data', [ChatRoomController::class, 'getChatRoomsData']); // Done
            Route::post('join/{chat_slug?}', [ChatRoomController::class, 'join'])->name('join.chatroom'); // Done
            Route::post('room/{chat_slug}/upload_cover', [ChatRoomController::class, 'uploadCover'])->can('community');

            Route::get('{category_slug}/{chat_slug}/members', [ChatRoomController::class, 'getChatRoomMembers'])->can('chat-room');// Done

            Route::post('add/{chat_slug}', [ChatRoomController::class, 'addMember'])->can('community'); // Done
            Route::get('{category_slug}/{chat_slug}/data', [ChatRoomController::class, 'getSingleChatPageMessages'])->can('chat-room');
            Route::put('{category_slug}/{chat_slug}/editDescription', [ChatRoomController::class, 'editRoomDescription'])->can('community');
            Route::post('{category_slug}/{chat_slug}/removeMember', [ChatRoomController::class, 'removeRoomMember'])->can('community');
            Route::post('{category_slug}/{room}/message', [ChatRoomController::class, 'postChatMessage'])->can('chat-room');
            Route::post('attachment', [ChatRoomController::class, 'newAttachment'])->can('chat-room'); //File
            Route::delete('message/{message_id}', [ChatRoomController::class, 'deleteMessage'])->can('community');
            Route::post('reaction', [ChatMessageReactController::class, 'saveChatReact'])->can('chat-room');
            Route::get('recentChatsSidebar', [ChatRoomController::class, 'recentChatsSidebar'])->can('chat-room'); //user and userProfile
        });
    });

    Route::get('question/{slug}', [QuestionController::class, 'singleQuestionData']); // Done
    Route::get('homepage/common', [CommunityHomePageController::class, 'getHomepageCommonData']); //user and userProfile
    Route::get('homepage/data', [QuestionController::class, 'getHomepageQuestionsData']); // Done

    Route::middleware("auth:sanctum")->group(function () {
        Route::prefix('questions')->group(function (){
            Route::post('{question_slug}/feedback', [QuestionController::class, 'storeFeedbackToQuestion']); // Done
            Route::post('new', [QuestionController::class, 'newQuestion']); // Done
            Route::post('attachment', [QuestionController::class, 'newAttachment']);
            Route::post('{question_slug}/answer/{answer_id}/feedback', [AnswerController::class, 'storeFeedbackToAnswer']); // Done

        });

        Route::post('question/{slug}/answer',  [AnswerController::class, 'newAnswer']); // Done
        Route::post('answer/attachment', [AnswerController::class, 'newAttachment']);

        });

    });


    // ================================================ PANEL =================================================
Route::middleware("auth:sanctum")->group(function () {

    Route::prefix('panel')->middleware('can:community')->group(function () {
        Route::prefix('question')->group(function () {

            Route::get('list', [QuestionController::class, 'getQuestionList']); // Done
            Route::put('set_status', [QuestionController::class, 'updateQuestionStatus']); // Done
            Route::get('list/common', [QuestionController::class, 'getQuestionListCommon']); // Done
            Route::put('question_edit', [QuestionController::class, 'createUpdateQuestion']);

        });

        Route::get('answers/list', [AnswerController::class, 'getAnswerList']); // Done
        Route::get('answers/list/common', [AnswerController::class, 'getAnswerListCommon']); // Done
        Route::put('answer/set_status', [AnswerController::class, 'updateAnswerStatus']); // Done
        Route::put('answer/answer_edit', [AnswerController::class, 'createUpdateAnswer']); //Done

        // ========== Chatrooms Panel ===========
        Route::prefix('chat')->group(function () {

            Route::get('rooms/common', [ChatRoomController::class, 'getChatRoomsPannelCommon']); // Done
            Route::get('rooms/data', [ChatRoomController::class, 'getChatRoomsPannelData'])->can('community'); // Done
            Route::put('set_status', [ChatRoomController::class, 'updateRoomStatus'])->can('community'); // Done
            Route::get('rooms/create/common', [ChatRoomController::class, 'getCreatChatRoomsPannelCommon'])->can('community'); // Done
            Route::put('rooms/editor-data', [ChatRoomController::class, 'createUpdateChatRoomsPannel'])->can('community'); // Done
            Route::get('show/{id}', [ChatRoomController::class, 'getChatData'])->can('community'); // Done
            Route::post('update/{room}', [ChatRoomController::class, 'updateChat'])->can('community'); // Done
            Route::delete('{slug}', [ChatRoomController::class, 'deleteRoom'])->can('community'); // Done

            Route::post('generate/join-link/{room}', [JoinController::class, 'generateJoinLink'])->can('community'); // Done
            Route::get('join/list/common', [JoinController::class, 'commonData'])->can('community'); // Done
            Route::get('join/list/data', [JoinController::class, 'listData'])->can('community'); // Done
            Route::put('join/set-status', [JoinController::class, 'updateStatus']);
        });
    });
});

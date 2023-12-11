<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use TechStudio\Community\app\Http\Controllers\AnswerController;
use TechStudio\Community\app\Http\Controllers\ChatMessageReactController;
use TechStudio\Community\app\Http\Controllers\ChatRoomController;
use TechStudio\Community\app\Http\Controllers\CommunityHomePageController;
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

// Route::get('/question/search', [SearchController::class, 'searchQuestion']);

Route::prefix('community')->group(function() {

    Route::prefix('chat')->group(function() {

        // Route::get('rooms/common', [ChatRoomController::class, 'getChatRoomsCommon']); // Done
        // Route::get('rooms/data', [ChatRoomController::class, 'getChatRoomsData']); // Done
        // Route::get('join/{chat_slug?}', [ChatRoomController::class,'join']); // Done
        // Route::post('room/{chat_slug}/upload_cover', [ChatRoomController::class,'uploadCover']);
        // Route::get('{category_slug}/{chat_slug}/common', [ChatRoomController::class,'getSingleChatPageCommonData']); // Done

        Route::middleware("auth:sanctum")->group(function () {

            // Route::post('add/{chat_slug}', [ChatRoomController::class,'addMember']); // Done
            // Route::get('{category_slug}/{chat_slug}/data', [ChatRoomController::class,'getSingleChatPageMessages']);
            // Route::put('{category_slug}/{chat_slug}/editDescription', [ChatRoomController::class,'editRoomDescription']);
            // Route::post('{category_slug}/{chat_slug}/removeMember', [ChatRoomController::class,'removeRoomMember']);
            // Route::post('{category_slug}/{room}/message', [ChatRoomController::class,'postChatMessage']);
            // Route::post('attachment', [ChatRoomController::class,'newAttachment']); //File
            // Route::delete('message/{message_id}', [ChatRoomController::class,'deleteMessage']);
            // Route::post('reaction', [ChatMessageReactController::class, 'saveChatReact']);
            // Route::get('recentChatsSidebar', [ChatRoomController::class,'recentChatsSidebar']); //user and userProfile
            // Route::get('question/{slug}', [QuestionController::class, 'singleQuestionData']); // Done
            // Route::get('homepage/common', [CommunityHomePageController::class, 'getHomepageCommonData']); //user and userProfile
            // Route::get('homepage/data', [QuestionController::class, 'getHomepageQuestionsData']); // Done

        });

    });

    Route::middleware("auth:sanctum")->group(function () {

        Route::prefix('questions')->group(function (){

            // Route::post('{question_slug}/feedback', [QuestionController::class, 'storeFeedbackToQuestion']); // Done
            // Route::post('new', [QuestionController::class, 'newQuestion']); // Done
            // Route::post('attachment', [QuestionController::class, 'newAttachment']);
            // Route::post('{question_slug}/answer/{answer_id}/feedback', [AnswerController::class, 'storeFeedbackToAnswer']); // Done

        });

        // Route::post('question/{slug}/answer',  [AnswerController::class, 'newAnswer']); // Done
        // Route::post('answer/attachment', [AnswerController::class, 'newAttachment']);

        });

    });


    // ================================================ PANEL =================================================
Route::middleware("auth:sanctum")->group(function () {

    Route::prefix('panel')->group(function () {

        Route::prefix('question')->group(function () {

            // Route::get('list', [QuestionController::class, 'getQuestionList']); // Done
            // Route::put('set_status', [QuestionController::class, 'updateQuestionStatus']); // Done
            // Route::get('list/common', [QuestionController::class, 'getQuestionListCommon']); // Done

        });

        // Route::get('answers/list', [AnswerController::class, 'getAnswerList']); // Done
        // Route::get('answers/list/common', [AnswerController::class, 'getAnswerListCommon']); // Done
        // Route::put('answer/set_status', [AnswerController::class, 'updateAnswerStatus']); // Done

        // ========== Chatrooms Panel ===========
        Route::prefix('chat')->group(function () {

            // Route::get('rooms/common', [ChatRoomController::class, 'getChatRoomsPannelCommon']); // Done
            // Route::get('rooms/data', [ChatRoomController::class, 'getChatRoomsPannelData'])->can('read_rooms'); // Done
            // Route::put('set_status', [ChatRoomController::class, 'updateRoomStatus'])->can('set_room_status'); // Done
            // Route::get('rooms/create/common', [ChatRoomController::class, 'getCreatChatRoomsPannelCommon'])->can('add_room'); // Done
            // Route::post('rooms/create', [ChatRoomController::class, 'createChatRoomsPannel'])->can('add_room'); // Done
            // Route::get('show/{room}', [ChatRoomController::class, 'getChatData'])->can('show_room'); // Done
            // Route::post('update/{room}', [ChatRoomController::class, 'updateChat'])->can('edit_room'); // Done
            // Route::delete('{slug}', [ChatRoomController::class, 'deleteRoom'])->can('delete_room'); // Done

        });
    });
});

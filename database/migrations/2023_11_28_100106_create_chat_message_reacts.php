<?php

namespace TechStudio\Community\database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_message_reacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('core_user_profiles');
            $table->foreignId('chat_id')->constrained('community_chat_messages');
            $table->enum('reaction',['like','dislike','clap','ok','happy','sad','smile']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_message_reacts');
    }
};

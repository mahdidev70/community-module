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
        Schema::create('community_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('community_chat_romms');
            $table->foreignId('user_id')->constrained('core_user_profiles');
            $table->boolean('is_seen')->default(false);
            $table->text('message')->nullable();
            $table->foreignId('reply_to')->nullable(); 
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_chat_messages');
    }
};

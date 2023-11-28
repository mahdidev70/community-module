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
        Schema::create('community_chat_room_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('core_user_profiles');
            $table->foreignId('chat_room_id')->constrained('community_chat_romms');
            $table->integer('unread_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_chat_room_memberships');
    }
};

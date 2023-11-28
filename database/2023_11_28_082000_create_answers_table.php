<?php

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
        Schema::create('community_answers', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->foreignId('question_id')->constrained();
            $table->foreignId('user_id')->constrained('user_profiles');
            $table->enum('status', ['approved','hidden','waiting_for_approval'])->default('waiting_for_approval');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_answers');
    }
};

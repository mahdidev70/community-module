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
        Schema::create('community_questions', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->string('slug')->unique();
            $table->foreignId('asker_user_id')->nullable()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->noActionOnDelete();
            $table->enum('status', ['approved','hidden','waiting_for_approval'])->default('waiting_for_approval');
            $table->integer('viewsCount')->nullable();
            $table->string('publication_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_questions');
    }
};

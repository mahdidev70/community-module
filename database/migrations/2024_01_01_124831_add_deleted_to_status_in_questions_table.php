<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use TechStudio\Community\app\Models\Question;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(app(Question::class)->getTable(), function (Blueprint $table) {
            DB::statement("ALTER TABLE ".app(Question::class)->getTable()." MODIFY COLUMN status ENUM('approved', 'hidden', 'waiting_for_approval','deleted') DEFAULT 'waiting_for_approval';");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            //
        });
    }
};

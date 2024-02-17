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
        Schema::table('community_chat_rooms', function (Blueprint $table) {
            $table->boolean('most_populer')->after('is_private')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('community_chat_rooms', function (Blueprint $table) {
            $table->dropColumn('most_populer');
        });
    }
};

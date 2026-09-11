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
        Schema::table('video_archives', function (Blueprint $table) {
            $table->unsignedBigInteger('views_count')->default(0)->after('published_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_archives', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });
    }
};

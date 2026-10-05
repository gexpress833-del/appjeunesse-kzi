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
        Schema::table('memberships', function (Blueprint $table) {
            // Drop foreign key constraint on user_id
            $table->dropForeign(['user_id']);
            // Drop user_id column
            $table->dropColumn('user_id');
            // Add member_id column with foreign key
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            // Drop foreign key constraint on member_id
            $table->dropForeign(['member_id']);
            // Drop member_id column
            $table->dropColumn('member_id');
            // Add user_id column with foreign key
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        });
    }
};

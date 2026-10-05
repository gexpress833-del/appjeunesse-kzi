<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('memberships', 'user_id')) {
            Schema::table('memberships', function (Blueprint $table): void {
                $table->dropForeign(['user_id']);
            });

            Schema::table('memberships', function (Blueprint $table): void {
                $table->dropColumn('user_id');
            });
        }

        if (! Schema::hasColumn('memberships', 'member_id')) {
            Schema::table('memberships', function (Blueprint $table): void {
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            });
        }

        DB::table('memberships')->whereNull('member_id')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('memberships', 'member_id')) {
            Schema::table('memberships', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('member_id');
            });
        }

        Schema::table('memberships', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('member_role_assignments', 'member_id')) {
            Schema::table('member_role_assignments', function (Blueprint $table): void {
                $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            });
        }

        DB::table('member_role_assignments')
            ->whereNotNull('user_id')
            ->get(['id', 'user_id'])
            ->each(function ($assignment): void {
                $memberId = DB::table('users')->whereKey($assignment->user_id)->value('member_id');

                if ($memberId === null) {
                    return;
                }

                DB::table('member_role_assignments')
                    ->whereKey($assignment->id)
                    ->update(['member_id' => $memberId]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('member_role_assignments', 'member_id')) {
            Schema::table('member_role_assignments', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('member_id');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ecodim_classes') || ! Schema::hasColumn('ecodim_classes', 'responsible_member_id')) {
            return;
        }

        Schema::table('ecodim_classes', function (Blueprint $table): void {
            $table->unsignedBigInteger('responsible_member_id_new')->nullable();
        });

        DB::table('ecodim_classes')
            ->whereNotNull('responsible_member_id')
            ->orderBy('id')
            ->get(['id', 'responsible_member_id'])
            ->each(function (object $class): void {
                $memberId = DB::table('users')
                    ->where('id', $class->responsible_member_id)
                    ->value('member_id');

                DB::table('ecodim_classes')
                    ->where('id', $class->id)
                    ->update(['responsible_member_id_new' => $memberId]);
            });

        Schema::table('ecodim_classes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('responsible_member_id');
        });

        Schema::table('ecodim_classes', function (Blueprint $table): void {
            $table->renameColumn('responsible_member_id_new', 'responsible_member_id');
        });

        Schema::table('ecodim_classes', function (Blueprint $table): void {
            $table->foreign('responsible_member_id')
                ->references('id')
                ->on('members')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ecodim_classes') || ! Schema::hasColumn('ecodim_classes', 'responsible_member_id')) {
            return;
        }

        Schema::table('ecodim_classes', function (Blueprint $table): void {
            $table->unsignedBigInteger('responsible_user_id_new')->nullable();
        });

        DB::table('ecodim_classes')
            ->whereNotNull('responsible_member_id')
            ->orderBy('id')
            ->get(['id', 'responsible_member_id'])
            ->each(function (object $class): void {
                $userId = DB::table('users')
                    ->where('member_id', $class->responsible_member_id)
                    ->orderBy('id')
                    ->value('id');

                DB::table('ecodim_classes')
                    ->where('id', $class->id)
                    ->update(['responsible_user_id_new' => $userId]);
            });

        Schema::table('ecodim_classes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('responsible_member_id');
        });

        Schema::table('ecodim_classes', function (Blueprint $table): void {
            $table->renameColumn('responsible_user_id_new', 'responsible_member_id');
        });

        Schema::table('ecodim_classes', function (Blueprint $table): void {
            $table->foreign('responsible_member_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};

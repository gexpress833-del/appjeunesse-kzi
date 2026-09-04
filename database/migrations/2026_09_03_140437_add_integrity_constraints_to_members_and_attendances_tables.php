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
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['dept']);
            $table->foreign('dept')
                ->references('name')
                ->on('departments')
                ->restrictOnDelete();
        });

        if (! Schema::hasIndex('attendances', ['member_id', 'event_id'])) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->unique(['member_id', 'event_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasIndex('attendances', ['member_id', 'event_id'])) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropUnique(['member_id', 'event_id']);
            });
        }

        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['dept']);
            $table->foreign('dept')
                ->references('name')
                ->on('departments')
                ->nullOnDelete();
        });
    }
};

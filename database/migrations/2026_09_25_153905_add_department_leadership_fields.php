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
        Schema::table('departments', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('name');
            $table->foreignId('leader_user_id')->nullable()->after('code')->constrained('users')->nullOnDelete();
            $table->foreignId('leader_assigned_by')->nullable()->after('leader_user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('leader_assigned_at')->nullable()->after('leader_assigned_by');
        });

        DB::table('departments')->where('name', 'Portail jeunesse')->update(['code' => 'youth']);
        DB::table('departments')->where('name', 'ECODIM')->update(['code' => 'ecodim']);
        DB::table('departments')->insertOrIgnore([
            ['name' => 'Portail jeunesse', 'code' => 'youth', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ECODIM', 'code' => 'ecodim', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['leader_user_id']);
            $table->dropForeign(['leader_assigned_by']);
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'leader_user_id', 'leader_assigned_by', 'leader_assigned_at']);
        });
    }
};

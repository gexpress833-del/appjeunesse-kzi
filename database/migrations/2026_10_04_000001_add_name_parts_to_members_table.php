<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            if (! Schema::hasColumn('members', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('members', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            if (Schema::hasColumn('members', 'last_name')) {
                $table->dropColumn('last_name');
            }

            if (Schema::hasColumn('members', 'first_name')) {
                $table->dropColumn('first_name');
            }
        });
    }
};

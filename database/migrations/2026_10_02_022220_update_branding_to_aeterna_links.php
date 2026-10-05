<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('app_settings')
            ->where('application_name', 'appjeunesse-kzi')
            ->update(['application_name' => 'AETERNA LINKS']);

        DB::table('app_settings')
            ->where('church_name', 'La Parole Éternelle Kolwezi')
            ->update(['church_name' => 'La Borne Parole Éternelle Kolwezi']);

        DB::table('churches')
            ->where('name', 'La Parole Éternelle Kolwezi')
            ->update(['name' => 'La Borne Parole Éternelle Kolwezi']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('app_settings')
            ->where('application_name', 'AETERNA LINKS')
            ->update(['application_name' => 'appjeunesse-kzi']);

        DB::table('app_settings')
            ->where('church_name', 'La Borne Parole Éternelle Kolwezi')
            ->update(['church_name' => 'La Parole Éternelle Kolwezi']);

        DB::table('churches')
            ->where('name', 'La Borne Parole Éternelle Kolwezi')
            ->update(['name' => 'La Parole Éternelle Kolwezi']);
    }
};

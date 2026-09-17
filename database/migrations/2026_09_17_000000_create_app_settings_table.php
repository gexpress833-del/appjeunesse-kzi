<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('church_name')->default('La Parole Éternelle Kolwezi');
            $table->string('application_name')->default('appjeunesse-kzi');
            $table->string('timezone')->default('Africa/Lubumbashi');
            $table->string('logo_url')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->json('attendance_statuses');
            $table->unsignedSmallInteger('attendance_editable_hours')->default(48);
            $table->json('notification_settings');
            $table->json('communication_settings');
            $table->boolean('maintenance_mode')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};

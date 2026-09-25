<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecodim_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('level')->nullable();
            $table->integer('age_min')->nullable();
            $table->integer('age_max')->nullable();
            $table->foreignId('responsible_member_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('ecodim_class_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('ecodim_classes')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('transition_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('source_space')->default('ecodim');
            $table->string('target_space')->default('youth');
            $table->integer('min_age')->nullable();
            $table->integer('max_age')->nullable();
            $table->date('active_from')->nullable();
            $table->date('active_to')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('ecodim_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('current_class_id')->nullable()->constrained('ecodim_classes')->nullOnDelete();
            $table->unsignedBigInteger('target_group_id')->nullable();
            $table->date('eligibility_date')->nullable();
            $table->string('status')->default('normal');
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transferred_at')->nullable();
            $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('postponement_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ecodim_transition_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transition_id')->constrained('ecodim_transitions')->cascadeOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecodim_transition_history');
        Schema::dropIfExists('ecodim_transitions');
        Schema::dropIfExists('transition_rules');
        Schema::dropIfExists('ecodim_class_members');
        Schema::dropIfExists('ecodim_classes');
    }
};

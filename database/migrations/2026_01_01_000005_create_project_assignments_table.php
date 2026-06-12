<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->string('priority')->default('medium');
            $table->unsignedTinyInteger('personal_progress')->default(0);
            $table->date('personal_due_at')->nullable();
            $table->text('personal_status_note')->nullable();
            $table->boolean('is_focused')->default(false);
            $table->date('snoozed_until')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'task_id']);
            $table->index(['user_id', 'priority']);
            $table->index(['user_id', 'is_focused']);
            $table->index(['user_id', 'snoozed_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_assignments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qa_review_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qa_review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_checklist_item_id')->constrained('project_checklist_items')->cascadeOnDelete();
            $table->string('verdict')->nullable(); // pass | fail
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['qa_review_id', 'project_checklist_item_id'], 'qa_review_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qa_review_items');
    }
};

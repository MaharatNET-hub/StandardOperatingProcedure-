<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * قاعدة بيانات "Project Notes" من وثيقة SRS (القسم 19–22): سجل تاريخي كامل
 * لكل ملاحظة/قرار/اجتماع متعلق بالمشروع. كل ملاحظة سجل مستقل لا يُستبدل
 * (BR-11) مع تسجيل تلقائي لتاريخها (BR-12) ولمن أضافها (BR-13).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->default('internal_note');
            $table->text('body')->nullable();
            $table->string('status')->default('new');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_notes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سجل تغيّر حالة متطلبات المشروع: متى صار الشعار جاهزاً، ومتى استُلم
 * المحتوى، ومن سجّل ذلك — بدل معرفة الحالة الحالية فقط دون تاريخها.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_requirement_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('requirement_key');
            $table->string('status'); // ready | in_progress | missing
            $table->date('effective_date');
            $table->text('note')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'requirement_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_requirement_updates');
    }
};

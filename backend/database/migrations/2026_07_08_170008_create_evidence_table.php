<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_checklist_item_id')->constrained('project_checklist_items')->cascadeOnDelete();
            $table->string('type'); // file | link
            $table->string('path')->nullable(); // storage path when type=file
            $table->string('original_name')->nullable();
            $table->string('url')->nullable(); // external link when type=link
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence');
    }
};

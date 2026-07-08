<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('client_name');
            $table->string('envato_preview_url')->nullable();
            $table->string('status')->default('in_progress'); // in_progress | in_review | changes_requested | approved | delivered
            $table->foreignId('current_phase_id')->nullable()->constrained('phases')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->date('content_deadline')->nullable();
            $table->unsignedInteger('revision_rounds_allowed')->default(2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};

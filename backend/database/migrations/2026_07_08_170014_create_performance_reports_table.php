<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('stage'); // original_template | final_site
            $table->unsignedInteger('lighthouse_mobile')->nullable();
            $table->unsignedInteger('lighthouse_desktop')->nullable();
            $table->string('pagespeed_url')->nullable();
            $table->string('screaming_frog_report_url')->nullable();
            $table->unsignedInteger('plugin_count')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('measured_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('measured_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reports');
    }
};

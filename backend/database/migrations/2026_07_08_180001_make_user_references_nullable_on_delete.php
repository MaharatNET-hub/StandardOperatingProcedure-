<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deleting a team member should never silently cascade-delete their
     * historical work (projects, evidence, plugin requests, QA reviews,
     * performance reports, signoffs) — only null out the "who did it"
     * reference so the records survive.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('evidence', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
        });
        Schema::table('evidence', function (Blueprint $table) {
            $table->foreignId('uploaded_by')->nullable()->change();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('plugin_requests', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
        });
        Schema::table('plugin_requests', function (Blueprint $table) {
            $table->foreignId('requested_by')->nullable()->change();
            $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('qa_reviews', function (Blueprint $table) {
            $table->dropForeign(['reviewer_id']);
        });
        Schema::table('qa_reviews', function (Blueprint $table) {
            $table->foreignId('reviewer_id')->nullable()->change();
            $table->foreign('reviewer_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('performance_reports', function (Blueprint $table) {
            $table->dropForeign(['measured_by']);
        });
        Schema::table('performance_reports', function (Blueprint $table) {
            $table->foreignId('measured_by')->nullable()->change();
            $table->foreign('measured_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('signoffs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('signoffs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Intentionally not reversible: restoring the old cascade-delete
        // behavior would risk silently deleting business data.
    }
};

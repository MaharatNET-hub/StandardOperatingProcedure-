<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('client_name')->nullable();
            $table->string('status')->default('draft'); // draft | sent | accepted | rejected

            // نتائج التحليل الفني (محاكاة Screaming Frog / Wappalyzer)
            $table->string('detected_framework')->nullable();
            $table->json('detected_signals')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('business_type')->nullable();
            $table->text('business_summary')->nullable();
            $table->json('infrastructure')->nullable();
            $table->string('recommended_platform')->nullable();
            $table->text('recommendation_reason')->nullable();

            // محتوى تقرير العرض (Quotation)
            $table->text('project_summary')->nullable();
            $table->text('technical_scope')->nullable();
            $table->json('cost_items')->nullable();
            $table->string('currency')->default('USD');
            $table->decimal('domain_cost', 10, 2)->nullable();
            $table->decimal('hosting_cost', 10, 2)->nullable();
            $table->string('hosting_cycle')->default('yearly'); // monthly | yearly
            $table->unsignedInteger('support_months')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};

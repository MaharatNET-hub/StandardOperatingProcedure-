<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // نظرة عامة
            $table->string('project_type')->nullable()->after('client_name');
            $table->text('project_description')->nullable()->after('project_type');
            $table->string('current_task')->nullable()->after('project_description');
            $table->string('pipeline_stage')->nullable()->after('status');
            $table->date('start_date')->nullable()->after('content_deadline');
            $table->dateTime('next_meeting_at')->nullable()->after('start_date');
            $table->foreignId('primary_developer_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();

            // الدومين والاستضافة
            $table->boolean('has_domain')->default(false);
            $table->string('domain_name')->nullable();
            $table->text('domain_login_info')->nullable();
            $table->string('domain_purchaser')->nullable();
            $table->boolean('has_hosting')->default(false);
            $table->string('hosting_provider')->nullable();
            $table->text('hosting_login_info')->nullable();
            $table->string('hosting_purchaser')->nullable();

            // الهوية والتصميم
            $table->boolean('has_logo')->default(false);
            $table->boolean('needs_logo_design')->default(false);
            $table->string('design_style')->nullable();
            $table->unsignedTinyInteger('website_languages_count')->nullable();
            $table->string('primary_language')->nullable();
            $table->string('secondary_language')->nullable();

            // التجارة الإلكترونية
            $table->boolean('needs_payment_gateway')->default(false);
            $table->string('payment_gateway_type')->nullable();
            $table->string('payment_gateway_status')->default('not_started');
            $table->boolean('has_shipping_company')->default(false);
            $table->string('shipping_company_name')->nullable();

            // المحتوى والتسويق
            $table->boolean('content_ready')->default(false);
            $table->boolean('product_images_ready')->default(false);
            $table->boolean('seo_required')->default(false);
            $table->string('seo_status')->default('not_started');
            $table->boolean('google_analytics_connected')->default(false);
            $table->boolean('search_console_connected')->default(false);

            // التواصل والسوشيال ميديا
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->boolean('social_media_available')->default(false);
            $table->text('social_media_login_info')->nullable();

            // متابعة العميل
            $table->dateTime('last_client_update_at')->nullable();
            $table->dateTime('next_client_update_at')->nullable();
            $table->unsignedTinyInteger('client_update_interval_days')->default(3);
            $table->string('client_feedback_status')->default('none');
            $table->text('client_feedback_notes')->nullable();
            $table->dateTime('client_feedback_at')->nullable();

            // ملاحظات وعوائق
            $table->text('client_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('blocker')->default('none');
            $table->boolean('website_uploaded')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_developer_id');
            $table->dropColumn([
                'project_type', 'project_description', 'current_task', 'pipeline_stage', 'start_date', 'next_meeting_at',
                'has_domain', 'domain_name', 'domain_login_info', 'domain_purchaser',
                'has_hosting', 'hosting_provider', 'hosting_login_info', 'hosting_purchaser',
                'has_logo', 'needs_logo_design', 'design_style', 'website_languages_count', 'primary_language', 'secondary_language',
                'needs_payment_gateway', 'payment_gateway_type', 'payment_gateway_status', 'has_shipping_company', 'shipping_company_name',
                'content_ready', 'product_images_ready', 'seo_required', 'seo_status', 'google_analytics_connected', 'search_console_connected',
                'phone', 'whatsapp', 'social_media_available', 'social_media_login_info',
                'last_client_update_at', 'next_client_update_at', 'client_update_interval_days',
                'client_feedback_status', 'client_feedback_notes', 'client_feedback_at',
                'client_notes', 'internal_notes', 'blocker', 'website_uploaded',
            ]);
        });
    }
};

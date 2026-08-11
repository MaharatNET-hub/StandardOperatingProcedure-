<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->longText('homepage_screenshot')->nullable()->after('proposed_pages');
            $table->unsignedTinyInteger('ux_score')->nullable()->after('homepage_screenshot');
            $table->unsignedTinyInteger('seo_score')->nullable()->after('ux_score');
            $table->unsignedTinyInteger('speed_score')->nullable()->after('seo_score');
            $table->text('audit_recommendation')->nullable()->after('speed_score');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['homepage_screenshot', 'ux_score', 'seo_score', 'speed_score', 'audit_recommendation']);
        });
    }
};

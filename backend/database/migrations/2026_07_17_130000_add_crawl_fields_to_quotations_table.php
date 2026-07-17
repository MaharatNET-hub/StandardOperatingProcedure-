<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->json('crawl_summary')->nullable()->after('recommendation_reason');
            $table->json('proposed_pages')->nullable()->after('crawl_summary');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['crawl_summary', 'proposed_pages']);
        });
    }
};

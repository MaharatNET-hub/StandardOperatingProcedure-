<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('performance_reports', function (Blueprint $table) {
            $table->string('source_url')->nullable()->after('stage');
            $table->boolean('is_automated')->default(false)->after('source_url');
            $table->json('issues')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('performance_reports', function (Blueprint $table) {
            $table->dropColumn(['source_url', 'is_automated', 'issues']);
        });
    }
};

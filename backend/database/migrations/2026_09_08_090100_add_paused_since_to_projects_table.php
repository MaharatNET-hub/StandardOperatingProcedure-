<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Date - Paused Since من وثيقة SRS (القسم 8): يسمح بمعرفة مدة بقاء المشروع
 * متوقفاً، وتُستخدم في لوحة "Paused — Monitor" (القسم 18.5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->date('paused_since')->nullable()->after('next_meeting_at');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('paused_since');
        });
    }
};

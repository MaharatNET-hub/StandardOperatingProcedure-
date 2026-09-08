<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تخصّص المبرمج: يميّز مبرمجي الووردبريس عن مبرمجي البرمجة الخاصة عن مبرمج
 * الفلاتر، حتى يمكن توزيع المشاريع وفلترة لوحات الأولويات حسب التخصّص.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('specialization')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('specialization');
        });
    }
};

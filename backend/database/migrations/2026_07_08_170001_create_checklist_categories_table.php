<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_categories', function (Blueprint $table) {
            $table->id();
            $table->string('group'); // quality | seo | media | launch
            $table->string('code')->unique(); // e.g. 3.1, 4.2, 5.3, 6.5
            $table->string('name_ar');
            $table->text('rule_note')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_categories');
    }
};

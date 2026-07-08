<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('role'); // prepared | reviewed | final_approval
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('signature_name');
            $table->timestamp('signed_at');
            $table->timestamps();
            $table->unique(['project_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signoffs');
    }
};

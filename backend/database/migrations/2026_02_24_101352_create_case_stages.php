<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Master stage list (admin-managed, lives inside Master Data Manager)
        Schema::create('case_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

       
        // 2. Full audit trail — every stage change is a permanent record
        Schema::create('case_stage_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('from_stage_id')->nullable()->constrained('case_stages')->nullOnDelete();
            $table->foreignId('to_stage_id')->constrained('case_stages')->restrictOnDelete();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->string('remarks', 255)->nullable();
            $table->timestamps();

            $table->index('case_id');
        });

        // 3. Add current_stage_id to cases table
        Schema::table('cases', function (Blueprint $table) {
            $table->foreignId('current_stage_id')
                  ->nullable()
                  ->after('case_status')
                  ->constrained('case_stages')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropForeign(['current_stage_id']);
            $table->dropColumn('current_stage_id');
        });

        Schema::dropIfExists('case_stage_histories');
        Schema::dropIfExists('case_stages');
    }
};
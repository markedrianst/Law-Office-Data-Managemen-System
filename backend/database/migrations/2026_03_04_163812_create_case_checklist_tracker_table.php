<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Folder IN/OUT movements ──────────────────────────────────────────
   Schema::create('folder_tracker', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')
                  ->constrained('cases')
                  ->cascadeOnDelete();
            $table->foreignId('recorded_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->enum('type', ['IN', 'OUT']);
            $table->string('from_to', 255)->nullable();   // recipient (OUT) or sender (IN)
            $table->date('date');
            $table->string('purpose', 500)->nullable();
            $table->string('handled_by', 255)->nullable();
            $table->timestamps();

            $table->index(['case_id', 'type']);
            $table->index('date');
        });

        // ── Checklist IN/OUT movements ───────────────────────────────────────
        Schema::create('checklist_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')
                  ->constrained('cases')
                  ->cascadeOnDelete();
            // nullable: NULL means the movement covers all tasks (general/bulk)
            $table->foreignId('checklist_id')
                  ->nullable()
                  ->constrained('case_checklists')
                  ->nullOnDelete();
            $table->foreignId('recorded_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->enum('type', ['IN', 'OUT']);
            $table->string('from_to', 255)->nullable();   // recipient (OUT) or sender (IN)
            $table->date('date');
            $table->string('purpose', 500)->nullable();
            $table->string('handled_by', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_movements');
        Schema::dropIfExists('folder_tracker');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Add is_out to case_checklists (the source of truth per item) ────
        Schema::table('case_checklists', function (Blueprint $table) {
            $table->boolean('is_out')
                  ->default(false)
                  ->after('notes');
        });

        Schema::table('cases', function (Blueprint $table) {
            $table->boolean('is_out')
                  ->default(false)
                  ->after('client_id');
        });

        // ── Add approval columns to checklist_movements if missing ───────────
        if (!Schema::hasColumn('checklist_movements', 'approval_status')) {
            Schema::table('checklist_movements', function (Blueprint $table) {
                $table->enum('approval_status', ['PENDING', 'APPROVED', 'REJECTED'])
                      ->default('PENDING')
                      ->after('handled_by');
                $table->foreignId('approved_by')
                      ->nullable()
                      ->after('approval_status')
                      ->constrained('users')
                      ->nullOnDelete();
                $table->timestamp('approved_at')
                      ->nullable()
                      ->after('approved_by');
            });
        }

        // ── Drop the case-level is_current from checklist_movements if it exists
        if (Schema::hasColumn('checklist_movements', 'is_current')) {
            Schema::table('checklist_movements', function (Blueprint $table) {
                $table->dropColumn('is_current');
            });
        }
    }

    public function down(): void
    {
        Schema::table('case_checklists', function (Blueprint $table) {
            $table->dropColumn('is_out');
        });
     
   Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn('is_out');
        });
        if (Schema::hasColumn('checklist_movements', 'approval_status')) {
            Schema::table('checklist_movements', function (Blueprint $table) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn(['approval_status', 'approved_by', 'approved_at']);
            });
        }
    }
};  
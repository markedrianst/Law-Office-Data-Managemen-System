<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add approval columns to folder_tracker (mirrors checklist_movements)
        if (!Schema::hasColumn('folder_tracker', 'approval_status')) {
            Schema::table('folder_tracker', function (Blueprint $table) {
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
                $table->string('approval_comment')
                      ->nullable()
                      ->after('approved_at');
            });
        }

        // Back-fill: any existing folder records were recorded by privileged users
        // (no clerks existed before this feature), so mark them all APPROVED.
        \DB::table('folder_tracker')
            ->whereNull('approval_status')
            ->orWhere('approval_status', '')
            ->update(['approval_status' => 'APPROVED']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('folder_tracker', 'approval_status')) {
            Schema::table('folder_tracker', function (Blueprint $table) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn(['approval_status', 'approved_by', 'approved_at']);
                $table->dropColumn(['approval_comment']);
            });
        }
    }
};

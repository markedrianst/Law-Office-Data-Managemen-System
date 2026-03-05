<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('case_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')
                  ->constrained('cases')
                  ->cascadeOnDelete();
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->string('task', 500);
            $table->enum('status', ['todo', 'in-progress', 'done'])->default('todo');
            $table->date('due_date')->nullable();
            $table->string('assigned_to', 255)->nullable();
            $table->foreignId('assigned_clerk_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['case_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_checklists');
    }
};
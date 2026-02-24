<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
            Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_code', 30)->unique();
            $table->string('case_no', 200);
            $table->string('title', 200);
            $table->foreignId('category_id')->nullable()->constrained('case_categories')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('court_or_office', 180)->nullable();
            $table->string('docket_no', 80)->nullable();
            $table->foreignId('assigned_lawyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_clerk_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('intake_status', [
                'draft',
                'for_approval',
                'approved',
                'returned'
            ])->default('for_approval');

            $table->enum('case_status', [
                'active',
                'closed',
                'archived'
            ])->default('active');

            $table->enum('priority', [
                'low',
                'normal',
                'urgent'
            ])->default('normal');

            $table->text('summary')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            
            $table->timestamps();

            $table->index(['category_id']);
            $table->index(['client_id']);
            $table->index(['assigned_lawyer_id']);
            $table->index(['assigned_clerk_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};

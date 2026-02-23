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
        Schema::create('case_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name',160)->unique();
        });
        \DB::table('case_categories')->insert([
            ['name' => 'Criminal'],
            ['name' => 'Annulment'],
            ['name' => 'Civil'],
            ['name' => 'Land Issues'],
            ['name' => 'Land Transfer'],
            ['name' => 'Pending'],
            ['name' => 'Admin'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_categories');
    }
};

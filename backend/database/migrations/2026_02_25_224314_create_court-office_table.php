    <?php
    // backend/database/migrations/[timestamp]_create_courts_table.php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Support\Facades\DB; // Add this import

    return new class extends Migration
    {
        public function up()
        {
            Schema::create('courts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->enum('type', ['Court', 'Prosecutor', 'Agency', 'Others'])->default('Court');
                $table->string('address')->nullable();
                $table->string('contact_info')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                
                // Indexes for faster searches
                $table->index('type');
                $table->index('is_active');
                $table->index('name');
            });
        }

        public function down()
        {
            Schema::dropIfExists('courts');
        }
    };
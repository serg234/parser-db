<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create `dumps` table.
 *
 * Stores metadata about uploaded `.sql` files (sources) and last run status.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dumps', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->string('stored_name')->unique();
            $table->string('relative_path')->unique();
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum', 64)->nullable()->index();
            $table->timestamp('last_parsed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dumps');
    }
};


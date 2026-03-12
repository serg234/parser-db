<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create `export_files` table.
 *
 * Stores generated export files:
 * - single: generated from one source (dump_id set)
 * - merged: generated from several sources (dump_id is null, linked via pivot)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('export_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dump_id')->nullable()->constrained('dumps')->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('format', 8);
            $table->string('filename');
            $table->string('relative_path')->unique();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedInteger('items_count')->nullable();
            $table->timestamps();

            $table->index(['type', 'format']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_files');
    }
};


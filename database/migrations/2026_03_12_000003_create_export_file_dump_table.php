<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create `export_file_dump` pivot table.
 *
 * Links merged exports with their source dumps.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('export_file_dump', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_file_id')->constrained('export_files')->cascadeOnDelete();
            $table->foreignId('dump_id')->constrained('dumps')->cascadeOnDelete();
            $table->unsignedInteger('position')->nullable();
            $table->timestamps();

            $table->unique(['export_file_id', 'dump_id']);
            $table->index(['dump_id', 'export_file_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_file_dump');
    }
};


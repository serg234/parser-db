<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks background export tasks for UI polling.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);   // single / merged
            $table->string('format', 10); // xml / csv / txt
            $table->string('status', 20)->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_tasks');
    }
};


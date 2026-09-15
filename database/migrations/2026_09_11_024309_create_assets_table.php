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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique(); // e.g. TRK-DSK-001
            $table->string('asset_name');              // e.g. Adjustable Standing Desk
            $table->string('category');                // e.g. Furniture
            $table->string('prefix');                  // e.g. DSK-
            $table->integer('unit_number');            // e.g. 1
            $table->string('location');                // e.g. Head Office
            $table->string('condition')->default('Good');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
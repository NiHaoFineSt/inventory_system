<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique(); // e.g. TRK-DSK-001
            $table->string('qr_code_payload')->nullable();
            $table->string('asset_name');                // e.g. Adjustable Standing Desk
            $table->string('category');                  // e.g. Furniture
            $table->string('prefix')->nullable();         // e.g. DSK-
            $table->string('unit_number')->nullable();    // zero-padded, e.g. "001"
            $table->string('location')->nullable();       // department / site
            $table->string('assigned_to')->nullable();    // person the unit is issued to
            $table->string('status')->default('Inactive');
            $table->string('condition')->default('Good');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};

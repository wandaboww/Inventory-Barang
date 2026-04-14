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
            $table->string('brand');
            $table->string('model');
            $table->string('serial_number')->unique();
            $table->string('category')->default('other');
            $table->enum('condition', ['good', 'minor_damage', 'major_damage', 'under_repair'])->default('good');
            $table->enum('status', ['available', 'borrowed', 'maintenance', 'lost'])->default('available');
            $table->string('barcode')->nullable()->unique();
            $table->string('qr_code_hash')->nullable()->unique();
            $table->json('specifications')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('status');
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

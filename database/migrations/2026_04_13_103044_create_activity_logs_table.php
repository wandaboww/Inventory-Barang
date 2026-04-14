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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('timestamp');
            $table->string('action');
            $table->string('table_name');
            $table->text('data');
            $table->text('details')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('timestamp');
            $table->index('table_name');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};

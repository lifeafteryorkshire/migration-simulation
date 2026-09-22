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
        Schema::create('legacy_addresses', function (Blueprint $table) {
            
            $table->unsignedBigInteger('AddressID')->primary();
            $table->string('AddressLine1')->nullable();
            $table->string('AddressLine2')->nullable();
            $table->string('TownCity')->nullable();
            $table->string('County')->nullable();
            $table->string('Postcode', 10)->nullable();
            $table->dateTime('DateCreated')->nullable();
            $table->boolean('Active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legacy_adresses');
    }
};

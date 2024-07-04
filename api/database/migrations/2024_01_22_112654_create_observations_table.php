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
        Schema::create('observations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Screen 1
            $table->string('Leq')->nullable();
            $table->json('LAeqT')->nullable();
            $table->string('LAmax')->nullable();
            $table->string('LAmin')->nullable();
            $table->string('L90')->nullable();
            $table->string('L10')->nullable();
            $table->string('sharpness_S')->nullable();
            $table->string('loudness_N')->nullable();
            $table->string('roughtness_R')->nullable();
            $table->string('fluctuation_strength_F')->nullable();
            // Screen 2
            $table->json('images')->nullable();
            // Screen 3
            $table->string('quiet')->nullable(); // question 2: How loud is the acoustic enviroment in this location?
            $table->string('cleanliness')->nullable(); //question 3: Please rate the overall cleanliness and maintenance of this location
            $table->string('accessibility')->nullable(); //question 4: please rate the overall accesibility of this location
            $table->string('safety')->nullable(); //question 5: rate overall safety
            $table->text('influence')->nullable(); //question 6: How do the sounds in this location influence your mood/emotions?
            $table->text('landmark')->nullable(); //question 7: is this sound a landmark? Soundmark
            $table->text('protection')->nullable(); //question 8: do you want this place to be protected? How?
            $table->geometry('coordinates'); // question 1: Please mark the location of the sound on the map
            $table->string('type');

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('observations');
    }
};

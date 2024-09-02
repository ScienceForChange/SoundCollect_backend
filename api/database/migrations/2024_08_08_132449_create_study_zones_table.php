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
        Schema::create('study_zones', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');

            $table->string('name');
            $table->string('description')->nullable()->default(NULL);
            $table->string('conclusion')->nullable()->default(NULL);
            $table->geometry('coordinates');
            $table->dateTime('start_date');
            $table->dateTime('end_date');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_zones');
    }
};

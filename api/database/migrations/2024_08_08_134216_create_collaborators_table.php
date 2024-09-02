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
        Schema::create('collaborators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('study_zone_id');

            $table->string('collaborator_name');
            $table->string('collaborator_web')->nullable()->default(NULL);
            $table->string('logo')->nullable()->default(NULL);
            $table->string('contact_name')->nullable()->default(NULL);
            $table->string('contact_email')->nullable()->default(NULL);
            $table->string('contact_phone')->nullable()->default(NULL);

            $table->timestamps();

            $table->foreign('study_zone_id')->references('id')->on('study_zones')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collaborators_study_zones');
    }
};

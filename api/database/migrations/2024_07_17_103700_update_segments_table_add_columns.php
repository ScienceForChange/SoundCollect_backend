<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('segments', function (Blueprint $table) {
            $table->decimal('fluctuation', 8, 2)->nullable();
            $table->decimal('sharpness', 8, 2)->nullable();
            $table->decimal('loudness', 8, 2)->nullable();
            $table->decimal('roughness', 8, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('segments', function (Blueprint $table) {
            $table->dropColumn(['fluctuation', 'sharpness', 'loudness', 'roughness']);
        });
    }

};

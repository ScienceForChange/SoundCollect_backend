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
            $table->integer('position')->nullable()->change();
        });

        // update observations table, set longitude, latitude and path to nullable
        Schema::table('observations', function (Blueprint $table) {
            $table->decimal('longitude', 8, 5)->nullable()->change();
            $table->decimal('latitude', 7, 5)->nullable()->change();
            $table->string('path')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Schema::table('segments', function (Blueprint $table) {
        //     // Assuming 'position' was originally not nullable
        //     $table->integer('position')->nullable(false)->change();
        // });
    }
};

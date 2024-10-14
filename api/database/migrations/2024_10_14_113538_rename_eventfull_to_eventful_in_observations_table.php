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
        Schema::table('observations', function (Blueprint $table) {
            $table->renameColumn('eventfull', 'eventful');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->renameColumn('eventful', 'eventfull');
        });
    }

};

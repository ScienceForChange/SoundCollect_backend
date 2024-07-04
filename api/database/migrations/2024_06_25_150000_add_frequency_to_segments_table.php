<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('segments', function (Blueprint $table) {
            $table->json('freq_3')->nullable();
            $table->json('spec_3')->nullable();
            $table->json('spec_3_dB')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('segments', function (Blueprint $table) {
            $table->dropColumn('freq_3');
            $table->dropColumn('spec_3');
            $table->dropColumn('spec_3_dB');
        });
    }
};

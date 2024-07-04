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
        Schema::table('segments', function (Blueprint $table) {
            $table->json('freq_3')->nullable();
            $table->json('spec_3')->nullable();
            $table->json('spec_3_dB')->nullable();

            $table->geometry('line');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('segments', function (Blueprint $table) {
            $table->dropColumn(['freq_3', 'spec_3', 'spec_3_db', 'line']);
        });
    }
};

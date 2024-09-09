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
        Schema::table('study_zones', function (Blueprint $table) {
            $table->renameColumn('user_id', 'admin_user_id');
            $table->dropForeign(['user_id']);
            $table->foreign('admin_user_id')->references('id')->on('admin_users')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_zones', function (Blueprint $table) {
            $table->dropForeign(['admin_user_id']);
            $table->renameColumn('admin_user_id', 'user_id');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade');
        });
    }
};

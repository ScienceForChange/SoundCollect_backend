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
            // Drop the existing foreign key constraint
            $table->dropForeign(['user_id']);

            // Make the user_id column nullable and ensure it matches the type and attributes of the referenced column
            $table->uuid('user_id')->nullable()->change();

            // Add a new foreign key constraint with onUpdate CASCADE and onDelete SET NULL
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');
        });

        // Update the column type and collation
        DB::statement('ALTER TABLE soundcollect.observations MODIFY COLUMN user_id char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('observations', function (Blueprint $table) {
            // Drop the modified foreign key constraint
            $table->dropForeign(['user_id']);

            // Change the user_id column back to not nullable (assuming it was not nullable before)
            $table->foreignId('user_id')->nullable(false)->change();

            // Recreate the original foreign key constraint without onDelete SET NULL
            // Note: Adjust the onUpdate and onDelete behavior as per the original constraint
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade');
        });
    }
};


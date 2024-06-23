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
        Schema::table('friendships', function (Blueprint $table) {
            //
            $table->string('status')->comment('active,pending,block')->default('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('friendships', function (Blueprint $table) {
            //
            $table->dropColumn('status');
        });
    }
};

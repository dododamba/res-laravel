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
        Schema::table('quartiers', function (Blueprint $table) {
            $table->string('chef_nom')->nullable();
            $table->string('chef_telephone')->nullable();
        });

        Schema::table('carres', function (Blueprint $table) {
            $table->string('chef_nom')->nullable();
            $table->string('chef_telephone')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quartiers', function (Blueprint $table) {
            $table->dropColumn(['chef_nom', 'chef_telephone']);
        });

        Schema::table('carres', function (Blueprint $table) {
            $table->dropColumn(['chef_nom', 'chef_telephone']);
        });
    }
};

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
        Schema::table('maisons', function (Blueprint $table) {
            $table->string('proprietaire_nom')->nullable();
            $table->string('proprietaire_telephone')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maisons', function (Blueprint $table) {
            $table->dropColumn(['proprietaire_nom', 'proprietaire_telephone']);
        });
    }
};

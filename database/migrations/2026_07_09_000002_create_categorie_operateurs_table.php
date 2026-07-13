<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorie_operateurs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uuid', 36)->unique();
            $table->string('nom', 255);
            $table->string('code', 50)->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('slug', 255)->unique();
            $table->integer('ordre_affichage')->default(0);
            $table->string('couleur', 7)->nullable();
            $table->string('icone', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('created_by', 255)->nullable();
            $table->string('updated_by', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorie_operateurs');
    }
};

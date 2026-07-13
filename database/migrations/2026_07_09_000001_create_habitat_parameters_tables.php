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
        $tables = [
            'categorie_activites',
            'type_batiments',
            'type_proprietes',
            'source_eaux',
            'source_energies',
            'assainissements',
            'gestion_dechets'
        ];

        foreach ($tables as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gestion_dechets');
        Schema::dropIfExists('assainissements');
        Schema::dropIfExists('source_energies');
        Schema::dropIfExists('source_eaux');
        Schema::dropIfExists('type_proprietes');
        Schema::dropIfExists('type_batiments');
        Schema::dropIfExists('categorie_activites');
    }
};

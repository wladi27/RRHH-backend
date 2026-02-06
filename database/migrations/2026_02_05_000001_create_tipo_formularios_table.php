<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_formularios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('slug')->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->json('metadatos')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('tipo_formularios')->insert([
            [
                'nombre' => 'Registro de Candidato',
                'slug' => 'registro-candidato',
                'descripcion' => 'Formulario para registro inicial de candidatos',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'Resultado de Entrevista con RRHH',
                'slug' => 'resultado-entrevista-rrhh',
                'descripcion' => 'Evaluación post-entrevista con recursos humanos',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'Resultado de Entrevista Técnica',
                'slug' => 'resultado-entrevista-tecnica',
                'descripcion' => 'Evaluación técnica de habilidades',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_formularios');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formularios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_formulario_id')->constrained('tipo_formularios')->onDelete('cascade');
            $table->string('nombre');
            $table->string('version')->default('1.0.0');
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->json('estructura')->nullable();
            $table->json('metadatos')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tipo_formulario_id', 'version']);
        });

        Schema::create('formulario_parametro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formulario_id')->constrained()->onDelete('cascade');
            $table->foreignId('parametro_formulario_id')->constrained()->onDelete('cascade');
            $table->integer('orden')->default(0);
            $table->boolean('requerido')->default(false);
            $table->json('configuracion_personalizada')->nullable();
            $table->timestamps();
            $table->unique(['formulario_id', 'parametro_formulario_id']);
        });

        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('codigo')->unique();
            $table->string('departamento')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('formulario_cargo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formulario_id')->constrained()->onDelete('cascade');
            $table->foreignId('cargo_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['formulario_id', 'cargo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formulario_cargo');
        Schema::dropIfExists('formulario_parametro');
        Schema::dropIfExists('formularios');
        Schema::dropIfExists('cargos');
    }
};

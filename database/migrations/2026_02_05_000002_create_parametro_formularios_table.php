<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametro_formularios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique()->comment('Clave técnica para JSON');
            $table->string('etiqueta')->comment('Texto visible para el usuario');
            $table->enum('tipo_dato', [
                'numero', 
                'texto', 
                'texto_largo', 
                'seleccion', 
                'seleccion_multiple', 
                'fecha', 
                'fecha_hora', 
                'archivo', 
                'casilla',
                'radio',
                'email',
                'telefono',
                'url'
            ])->default('texto');
            $table->string('texto_ayuda')->nullable()->comment('Placeholder');
            $table->boolean('requerido')->default(false);
            $table->string('fuente_opciones')->nullable()->comment('Endpoint API o lista estática');
            $table->text('expresion_regular')->nullable();
            $table->text('mensaje_validacion')->nullable();
            $table->integer('orden_defecto')->default(0);
            $table->json('regla_visibilidad')->nullable();
            $table->json('configuracion_adicional')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('parametro_formularios')->insert([
            [
                'nombre' => 'anios_experiencia',
                'etiqueta' => 'Años de Experiencia',
                'tipo_dato' => 'numero',
                'texto_ayuda' => 'Ej: 5',
                'requerido' => true,
                'fuente_opciones' => null,
                'expresion_regular' => '^[0-9]+$',
                'mensaje_validacion' => null,
                'orden_defecto' => 10,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'nombre_completo',
                'etiqueta' => 'Nombre Completo',
                'tipo_dato' => 'texto',
                'texto_ayuda' => null,
                'requerido' => true,
                'fuente_opciones' => null,
                'expresion_regular' => null,
                'mensaje_validacion' => null,
                'orden_defecto' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'correo_electronico',
                'etiqueta' => 'Correo Electrónico',
                'tipo_dato' => 'email',
                'texto_ayuda' => 'ejemplo@empresa.com',
                'requerido' => true,
                'fuente_opciones' => null,
                'expresion_regular' => '^[\\w-\\.]+@([\\w-]+\\.)+[\\w-]{2,4}$',
                'mensaje_validacion' => null,
                'orden_defecto' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('parametro_formularios');
    }
};

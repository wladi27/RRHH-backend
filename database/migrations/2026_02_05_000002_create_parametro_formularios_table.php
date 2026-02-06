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
            $table->string('name')->unique()->comment('Clave técnica para JSON');
            $table->string('label')->comment('Texto visible para el usuario');
            $table->enum('data_type', [
                'number',
                'text',
                'textarea',
                'select',
                'select_multiple',
                'date',
                'datetime',
                'file',
                'checkbox',
                'radio',
                'email',
                'tel',
                'url'
            ])->default('text');
            $table->string('placeholder')->nullable()->comment('Texto de ayuda');
            $table->boolean('required')->default(false);
            $table->string('options_source')->nullable()->comment('Endpoint API o lista estática');
            $table->string('validation_regex')->nullable();
            $table->integer('order')->default(0);
            $table->json('visibility_rule')->nullable();
            $table->timestamps();
        });

        DB::table('parametro_formularios')->insert([
            [
                'name' => 'anios_experiencia',
                'label' => 'Años de Experiencia',
                'data_type' => 'number',
                'placeholder' => 'Ej: 5',
                'required' => true,
                'options_source' => null,
                'validation_regex' => '^[0-9]+$',
                'order' => 10,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'nombre_completo',
                'label' => 'Nombre Completo',
                'data_type' => 'text',
                'placeholder' => null,
                'required' => true,
                'options_source' => null,
                'validation_regex' => null,
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'correo_electronico',
                'label' => 'Correo Electrónico',
                'data_type' => 'email',
                'placeholder' => 'ejemplo@empresa.com',
                'required' => true,
                'options_source' => null,
                'validation_regex' => '^[\\w-\\.]+@([\\w-]+\\.)+[\\w-]{2,4}$',
                'order' => 2,
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

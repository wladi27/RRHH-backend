<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametro_formularios', function (Blueprint $table) {
            if (!Schema::hasColumn('parametro_formularios', 'name')) {
                $table->string('name')->nullable()->after('nombre');
            }
            if (!Schema::hasColumn('parametro_formularios', 'label')) {
                $table->string('label')->nullable()->after('etiqueta');
            }
            if (!Schema::hasColumn('parametro_formularios', 'data_type')) {
                $table->string('data_type')->nullable()->after('tipo_dato');
            }
            if (!Schema::hasColumn('parametro_formularios', 'placeholder')) {
                $table->string('placeholder')->nullable()->after('texto_ayuda');
            }
            if (!Schema::hasColumn('parametro_formularios', 'help_text')) {
                $table->text('help_text')->nullable()->after('configuracion_adicional');
            }
            if (!Schema::hasColumn('parametro_formularios', 'required')) {
                $table->boolean('required')->default(false)->after('requerido');
            }
            if (!Schema::hasColumn('parametro_formularios', 'options_source')) {
                $table->json('options_source')->nullable()->after('fuente_opciones');
            }
            if (!Schema::hasColumn('parametro_formularios', 'validation')) {
                $table->json('validation')->nullable()->after('expresion_regular');
            }
            if (!Schema::hasColumn('parametro_formularios', 'order')) {
                $table->integer('order')->nullable()->after('orden_defecto');
            }
            if (!Schema::hasColumn('parametro_formularios', 'visibility_rule')) {
                $table->json('visibility_rule')->nullable()->after('regla_visibilidad');
            }
            if (!Schema::hasColumn('parametro_formularios', 'default_value')) {
                $table->text('default_value')->nullable()->after('configuracion_adicional');
            }
        });
    }

    public function down(): void
    {
        Schema::table('parametro_formularios', function (Blueprint $table) {
            $cols = ['name','label','data_type','placeholder','help_text','required','options_source','validation','order','visibility_rule','default_value'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('parametro_formularios', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};

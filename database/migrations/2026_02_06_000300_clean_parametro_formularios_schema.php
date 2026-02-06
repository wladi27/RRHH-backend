<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametro_formularios', function (Blueprint $table) {
            if (!Schema::hasColumn('parametro_formularios', 'validation_regex')) {
                $table->string('validation_regex')->nullable()->after('expresion_regular');
            }
        });

        DB::statement("
            UPDATE parametro_formularios
            SET
                name = COALESCE(name, nombre),
                label = COALESCE(label, etiqueta),
                data_type = COALESCE(
                    data_type,
                    CASE tipo_dato
                        WHEN 'numero' THEN 'number'
                        WHEN 'texto' THEN 'text'
                        WHEN 'texto_largo' THEN 'textarea'
                        WHEN 'seleccion' THEN 'select'
                        WHEN 'seleccion_multiple' THEN 'select_multiple'
                        WHEN 'fecha' THEN 'date'
                        WHEN 'fecha_hora' THEN 'datetime'
                        WHEN 'archivo' THEN 'file'
                        WHEN 'casilla' THEN 'checkbox'
                        WHEN 'radio' THEN 'radio'
                        WHEN 'email' THEN 'email'
                        WHEN 'telefono' THEN 'tel'
                        WHEN 'url' THEN 'url'
                        ELSE tipo_dato
                    END
                ),
                placeholder = COALESCE(placeholder, texto_ayuda),
                required = COALESCE(required, requerido, false),
                validation_regex = COALESCE(validation_regex, expresion_regular),
                \"order\" = COALESCE(\"order\", orden_defecto, 0),
                visibility_rule = COALESCE(visibility_rule, regla_visibilidad)
        ");

        Schema::table('parametro_formularios', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('label')->nullable(false)->change();
            $table->string('data_type')->default('text')->change();
            $table->string('placeholder')->nullable()->change();
            $table->boolean('required')->default(false)->change();
            $table->string('options_source')->nullable()->change();
            $table->string('validation_regex')->nullable()->change();
            $table->integer('order')->default(0)->change();
            $table->json('visibility_rule')->nullable()->change();

            if (Schema::hasColumn('parametro_formularios', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        DB::statement("
            UPDATE parametro_formularios
            SET
                options_source = COALESCE(options_source, fuente_opciones)
        ");

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM pg_constraint
                    WHERE conname = 'parametro_formularios_data_type_check'
                ) THEN
                    ALTER TABLE parametro_formularios
                    ADD CONSTRAINT parametro_formularios_data_type_check
                    CHECK (data_type IN (
                        'number','text','textarea','select','select_multiple',
                        'date','datetime','file','checkbox','radio','email','tel','url'
                    ));
                END IF;
            END
            $$;
        ");

        Schema::table('parametro_formularios', function (Blueprint $table) {
            $dropColumns = [
                'nombre',
                'etiqueta',
                'tipo_dato',
                'texto_ayuda',
                'requerido',
                'fuente_opciones',
                'expresion_regular',
                'mensaje_validacion',
                'orden_defecto',
                'regla_visibilidad',
                'configuracion_adicional',
                'help_text',
                'validation',
                'default_value'
            ];
            foreach ($dropColumns as $column) {
                if (Schema::hasColumn('parametro_formularios', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('parametro_formularios', function (Blueprint $table) {
            if (!Schema::hasColumn('parametro_formularios', 'nombre')) {
                $table->string('nombre')->nullable()->after('id');
            }
            if (!Schema::hasColumn('parametro_formularios', 'etiqueta')) {
                $table->string('etiqueta')->nullable()->after('nombre');
            }
            if (!Schema::hasColumn('parametro_formularios', 'tipo_dato')) {
                $table->string('tipo_dato')->nullable()->after('etiqueta');
            }
            if (!Schema::hasColumn('parametro_formularios', 'texto_ayuda')) {
                $table->string('texto_ayuda')->nullable()->after('tipo_dato');
            }
            if (!Schema::hasColumn('parametro_formularios', 'requerido')) {
                $table->boolean('requerido')->default(false)->after('texto_ayuda');
            }
            if (!Schema::hasColumn('parametro_formularios', 'fuente_opciones')) {
                $table->string('fuente_opciones')->nullable()->after('requerido');
            }
            if (!Schema::hasColumn('parametro_formularios', 'expresion_regular')) {
                $table->text('expresion_regular')->nullable()->after('fuente_opciones');
            }
            if (!Schema::hasColumn('parametro_formularios', 'mensaje_validacion')) {
                $table->text('mensaje_validacion')->nullable()->after('expresion_regular');
            }
            if (!Schema::hasColumn('parametro_formularios', 'orden_defecto')) {
                $table->integer('orden_defecto')->default(0)->after('mensaje_validacion');
            }
            if (!Schema::hasColumn('parametro_formularios', 'regla_visibilidad')) {
                $table->json('regla_visibilidad')->nullable()->after('orden_defecto');
            }
            if (!Schema::hasColumn('parametro_formularios', 'configuracion_adicional')) {
                $table->json('configuracion_adicional')->nullable()->after('regla_visibilidad');
            }
            if (!Schema::hasColumn('parametro_formularios', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }
};

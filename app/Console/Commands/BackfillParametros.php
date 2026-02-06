<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ParametroFormulario;
use Illuminate\Support\Facades\DB;

class BackfillParametros extends Command
{
    protected $signature = 'parametros:backfill';

    protected $description = 'Normaliza fuente_opciones, rellena configuracion_adicional y actualiza pivot configuracion_personalizada para parámetros';

    public function handle(): int
    {
        $this->info('Iniciando backfill de parametros...');

        $parametros = ParametroFormulario::all();
        $bar = $this->output->createProgressBar($parametros->count());
        $bar->start();

        foreach ($parametros as $p) {
            // Use a transaction per-parameter to ensure consistent writes
            DB::transaction(function () use ($p, &$bar) {
                $changed = false;

            // Normalizar fuente_opciones a objeto JSON {type: 'static'|'api', value: ...}
            $fuente = $p->fuente_opciones;
            $normalizedFuente = null;
            if ($fuente) {
                $trim = trim($fuente);
                $decoded = @json_decode($trim, true);
                if (is_array($decoded) && array_key_exists('type', $decoded)) {
                    // already structured
                    $normalizedFuente = $decoded;
                } elseif (is_array($decoded)) {
                    // array of options -> static
                    $normalizedFuente = ['type' => 'static', 'value' => array_values($decoded)];
                } else {
                    // plain string: could be csv or endpoint
                    if (strpos($trim, ',') !== false) {
                        $parts = array_map('trim', explode(',', $trim));
                        $normalizedFuente = ['type' => 'static', 'value' => $parts];
                    } elseif (preg_match('#^https?://#', $trim) || strpos($trim, '/') === 0) {
                        $normalizedFuente = ['type' => 'api', 'value' => $trim];
                    } else {
                        // fallback: keep as api endpoint
                        $normalizedFuente = ['type' => 'api', 'value' => $trim];
                    }
                }

                if ($normalizedFuente) {
                    $p->fuente_opciones = json_encode($normalizedFuente);
                    $changed = true;
                }
            }

            // Rellenar configuracion_adicional si está vacía
            $config = $p->configuracion_adicional ?? [];
            if (!is_array($config)) $config = [];

            if (empty($config) || !array_key_exists('help_text', $config)) {
                if ($p->texto_ayuda) {
                    $config['help_text'] = $p->texto_ayuda;
                    $changed = true;
                }
            }

            if (!array_key_exists('validation', $config) && $p->expresion_regular) {
                $config['validation'] = ['regex' => $p->expresion_regular];
                $changed = true;
            }

                if ($changed) {
                    $p->configuracion_adicional = $config;
                    // don't save yet
                }

                // Poblar las columnas normalizadas si están vacías (uno save al final)
                $updated = false;
                if (empty($p->name)) { $p->name = $p->nombre; $updated = true; }
                if (empty($p->label)) { $p->label = $p->etiqueta; $updated = true; }
                if (empty($p->data_type)) { $p->data_type = $p->tipo_dato; $updated = true; }
                if (empty($p->placeholder) && !empty($p->texto_ayuda)) { $p->placeholder = $p->texto_ayuda; $updated = true; }
                if (empty($p->help_text) && !empty($config['help_text'])) { $p->help_text = $config['help_text']; $updated = true; }
                if (is_null($p->required)) { $p->required = (bool) $p->requerido; $updated = true; }
                if (empty($p->options_source) && !empty($p->fuente_opciones)) {
                    $decoded = @json_decode($p->fuente_opciones, true);
                    $p->options_source = $decoded ?: $p->fuente_opciones;
                    $updated = true;
                }
                if (empty($p->validation)) {
                    if (!empty($config['validation'])) { $p->validation = $config['validation']; $updated = true; }
                    elseif (!empty($p->expresion_regular)) { $p->validation = ['regex' => $p->expresion_regular]; $updated = true; }
                }
                if (empty($p->order) && !is_null($p->orden_defecto)) { $p->order = $p->orden_defecto; $updated = true; }
                if (empty($p->visibility_rule) && !empty($p->regla_visibilidad)) { $p->visibility_rule = $p->regla_visibilidad; $updated = true; }
                if (empty($p->default_value) && array_key_exists('default_value', $config)) { $p->default_value = $config['default_value']; $updated = true; }

                if ($changed || $updated) { $p->save(); }

                // Update pivot tabla formulario_parametro: set configuracion_personalizada si empty
                $rows = DB::table('formulario_parametro')->where('parametro_formulario_id', $p->id)->get();
                foreach ($rows as $row) {
                    if (empty($row->configuracion_personalizada)) {
                        $pivotConfig = [];
                        if (!empty($p->fuente_opciones)) {
                            $decoded = @json_decode($p->fuente_opciones, true);
                            $pivotConfig['options_source'] = $decoded ?: $p->fuente_opciones;
                        }
                        if (!empty($p->regla_visibilidad)) {
                            $pivotConfig['visibility_rule'] = $p->regla_visibilidad;
                        }
                        if (!empty($p->configuracion_adicional) && is_array($p->configuracion_adicional)) {
                            if (array_key_exists('default_value', $p->configuracion_adicional)) {
                                $pivotConfig['default_value'] = $p->configuracion_adicional['default_value'];
                            }
                            if (array_key_exists('help_text', $p->configuracion_adicional)) {
                                $pivotConfig['help_text'] = $p->configuracion_adicional['help_text'];
                            }
                        }

                        DB::table('formulario_parametro')->where('id', $row->id)->update([
                            'configuracion_personalizada' => json_encode($pivotConfig)
                        ]);
                    }
                }

                $bar->advance();
            });
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Backfill completado.');

        return 0;
    }
}

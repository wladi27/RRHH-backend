<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formulario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ConstructorFormularioController extends Controller
{
    public function crearFormulario(Request $request): JsonResponse
    {
        $validated = $this->validarDatosFormulario($request);

        DB::beginTransaction();
        try {
            $existe = Formulario::where('tipo_formulario_id', $validated['tipo_formulario_id'])
                ->where('version', $validated['version'])
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ya existe un formulario con esta versión para este tipo'
                ], 409);
            }

            $formulario = Formulario::create([
                'tipo_formulario_id' => $validated['tipo_formulario_id'],
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
                'version' => $validated['version'],
                'estructura' => $this->generarEstructura($validated['parametros'])
            ]);

            foreach ($validated['parametros'] as $parametro) {
                $config = $parametro['configuracion_personalizada'] ?? [];
                // respetar options_source y visibility_rule si vienen en el payload del parámetro
                if (isset($parametro['options_source'])) {
                    $config['options_source'] = $parametro['options_source'];
                }
                if (isset($parametro['visibility_rule'])) {
                    $config['visibility_rule'] = $parametro['visibility_rule'];
                }

                $formulario->parametros()->attach($parametro['id'], [
                    'orden' => $parametro['orden'] ?? 0,
                    'requerido' => $parametro['requerido'] ?? false,
                    'configuracion_personalizada' => json_encode($config)
                ]);
            }

            if (isset($validated['cargos']) && is_array($validated['cargos'])) {
                $formulario->cargos()->attach($validated['cargos']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $formulario->load(['parametros', 'cargos', 'tipoFormulario']),
                'message' => 'Formulario creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'error' => 'Error al crear el formulario: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateFormulario(Request $request, Formulario $formulario): JsonResponse
    {
        $validated = $this->validarDatosFormulario($request);

        DB::beginTransaction();
        try {
            $existe = Formulario::where('tipo_formulario_id', $validated['tipo_formulario_id'])
                ->where('version', $validated['version'])
                ->where('id', '!=', $formulario->id)
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ya existe un formulario con esta versión para este tipo'
                ], 409);
            }

            $formulario->update([
                'tipo_formulario_id' => $validated['tipo_formulario_id'],
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
                'version' => $validated['version'],
                'estructura' => $this->generarEstructura($validated['parametros'])
            ]);

            // preparar datos para sync
            $sync = [];
            foreach ($validated['parametros'] as $parametro) {
                $config = $parametro['configuracion_personalizada'] ?? [];
                if (isset($parametro['options_source'])) {
                    $config['options_source'] = $parametro['options_source'];
                }
                if (isset($parametro['visibility_rule'])) {
                    $config['visibility_rule'] = $parametro['visibility_rule'];
                }

                $sync[$parametro['id']] = [
                    'orden' => $parametro['orden'] ?? 0,
                    'requerido' => $parametro['requerido'] ?? false,
                    'configuracion_personalizada' => json_encode($config)
                ];
            }

            $formulario->parametros()->sync($sync);

            if (isset($validated['cargos']) && is_array($validated['cargos'])) {
                $formulario->cargos()->sync($validated['cargos']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $formulario->load(['parametros', 'cargos', 'tipoFormulario']),
                'message' => 'Formulario actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar el formulario: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clonarFormulario(Request $request, Formulario $formulario): JsonResponse
    {
        $validated = $request->validate([
            'nuevo_nombre' => 'required|string|max:255',
            'nueva_version' => 'required|string'
        ]);

        DB::beginTransaction();
        try {
            $existe = Formulario::where('tipo_formulario_id', $formulario->tipo_formulario_id)
                ->where('version', $validated['nueva_version'])
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ya existe un formulario con esta versión'
                ], 409);
            }

            $nuevoFormulario = $formulario->clonar(
                $validated['nuevo_nombre'],
                $validated['nueva_version']
            );

            if (!$nuevoFormulario) {
                throw new \Exception('Error al clonar el formulario');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $nuevoFormulario,
                'message' => 'Formulario clonado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'error' => 'Error al clonar el formulario: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $query = Formulario::with(['tipoFormulario', 'parametros'])->orderBy('created_at', 'desc');

        if ($request->has('tipo')) {
            $query->whereHas('tipoFormulario', function ($q) use ($request) {
                $q->where('slug', $request->tipo);
            });
        }

        $formularios = $query->get();

        return response()->json([
            'success' => true,
            'data' => $formularios,
            'message' => 'Formularios obtenidos correctamente'
        ]);
    }

    public function obtenerEstructura(Formulario $formulario): JsonResponse
    {
        $estructura = [
            'id' => $formulario->id,
            'nombre' => $formulario->nombre,
            'version' => $formulario->version,
            'tipo_formulario' => $formulario->tipoFormulario->nombre,
            'descripcion' => $formulario->descripcion,
            'campos' => []
        ];

        $parametros = $formulario->parametros()->orderByPivot('orden')->get();

        foreach ($parametros as $parametro) {
            $campo = [
                'nombre' => $parametro->name,
                'etiqueta' => $parametro->label,
                'tipo' => $parametro->data_type,
                'texto_ayuda' => $parametro->placeholder,
                'requerido' => (bool) $parametro->pivot->requerido,
                'validacion' => [
                    'expresion_regular' => $parametro->validation_regex,
                    'mensaje' => null
                ],
                'orden' => $parametro->pivot->orden
            ];

            if (in_array($parametro->data_type, ['select', 'select_multiple', 'radio'])) {
                $campo['fuente_opciones'] = $parametro->options_source;
            }

            if ($parametro->pivot->configuracion_personalizada) {
                $configPersonalizada = json_decode($parametro->pivot->configuracion_personalizada, true);
                $campo = array_merge($campo, $configPersonalizada);
            }

            if ($parametro->visibility_rule) {
                $campo['regla_visibilidad'] = $parametro->visibility_rule;
            }

            $estructura['campos'][] = $campo;
        }

        return response()->json([
            'success' => true,
            'data' => $estructura,
            'message' => 'Estructura del formulario obtenida'
        ]);
    }

    private function validarDatosFormulario(Request $request): array
    {
        return $request->validate([
            'tipo_formulario_id' => 'required|exists:tipo_formularios,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'version' => 'required|string',
            'parametros' => 'required|array|min:1',
            'parametros.*.id' => 'required|exists:parametro_formularios,id',
            'parametros.*.orden' => 'integer|min:0',
            'parametros.*.requerido' => 'boolean',
            'parametros.*.configuracion_personalizada' => 'nullable|array',
            'parametros.*.options_source' => 'nullable',
            'parametros.*.visibility_rule' => 'nullable|array',
            'cargos' => 'nullable|array',
            'cargos.*' => 'exists:cargo,id'
        ]);
    }

    private function generarEstructura(array $parametros): array
    {
        return [
            'esquema' => 'https://json-schema.org/draft/2020-12/schema',
            'tipo' => 'objeto',
            'propiedades' => [],
            'requeridos' => [],
            'metadatos' => [
                'generado_el' => now()->toISOString(),
                'total_campos' => count($parametros)
            ]
        ];
    }
}

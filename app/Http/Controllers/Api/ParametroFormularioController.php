<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParametroFormulario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ParametroFormularioController extends Controller
{
    private array $tiposDatoPermitidos = [
        // usar los tipos normalizados que exponemos en la API
        'number','text','select','date','file','checkbox','radio','textarea','select_multiple','datetime','email','tel','url'
    ];

    public function index(Request $request): JsonResponse
    {
        $query = ParametroFormulario::query();

        if ($request->has('tipo_dato')) {
            $query->where('tipo_dato', $request->tipo_dato);
        }

        if ($request->has('buscar')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'ilike', '%' . $request->buscar . '%')
                  ->orWhere('etiqueta', 'ilike', '%' . $request->buscar . '%');
            });
        }

        $query->orderBy('orden_defecto');

        $parametros = $query->get();

        // Mapear a esquema público (solo formato solicitado)
        $mapped = $parametros->map(function ($p) {
            return $this->mapPublic($p);
        });

        return response()->json([
            'success' => true,
            'data' => $mapped,
            'message' => 'Parámetros obtenidos correctamente'
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Aceptar nombres del documento y mapearlos a columnas
        $input = $request->all();
        if (isset($input['name'])) $request->merge(['nombre' => $input['name']]);
        if (isset($input['label'])) $request->merge(['etiqueta' => $input['label']]);
        if (isset($input['data_type'])) $request->merge(['tipo_dato' => $input['data_type']]);
        if (isset($input['placeholder'])) $request->merge(['texto_ayuda' => $input['placeholder']]);
        if (isset($input['required'])) $request->merge(['requerido' => $input['required']]);
        if (isset($input['order'])) $request->merge(['orden_defecto' => $input['order']]);
        if (isset($input['validation']) && is_array($input['validation']) && isset($input['validation']['regex'])) {
            $request->merge(['expresion_regular' => $input['validation']['regex']]);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:parametro_formularios',
            'etiqueta' => 'required|string|max:255',
            'tipo_dato' => 'required|in:' . implode(',', $this->tiposDatoPermitidos),
            'texto_ayuda' => 'nullable|string|max:255',
            'requerido' => 'boolean',
            'fuente_opciones' => 'nullable|string',
            'options_source' => 'nullable',
            'expresion_regular' => 'nullable|string',
            'mensaje_validacion' => 'nullable|string',
            'orden_defecto' => 'integer|min:0',
            'regla_visibilidad' => 'nullable|array',
            'configuracion_adicional' => 'nullable|array'
        ]);

        // Map options_source
        if ($request->has('options_source')) {
            $opts = $request->input('options_source');
            $validated['fuente_opciones'] = is_string($opts) ? $opts : json_encode($opts);
        }
        // Map visibility_rule
        if ($request->has('visibility_rule')) {
            $validated['regla_visibilidad'] = $request->input('visibility_rule');
        }

        // Map help_text/default_value from top-level if provided
        if ($request->has('help_text') || $request->has('default_value') || $request->has('validation')) {
            $conf = $validated['configuracion_adicional'] ?? [];
            if ($request->has('help_text')) $conf['help_text'] = $request->input('help_text');
            if ($request->has('default_value')) $conf['default_value'] = $request->input('default_value');
            if ($request->has('validation')) $conf['validation'] = $request->input('validation');
            $validated['configuracion_adicional'] = $conf;
        }

        // Crear registro (legacy fields)
        $parametro = ParametroFormulario::create($validated);

        // Actualizar columnas normalizadas si no vienen en la request
        $norm = [
            'name' => $request->input('name', $parametro->nombre),
            'label' => $request->input('label', $parametro->etiqueta),
            'data_type' => $request->input('data_type', $parametro->tipo_dato),
            'placeholder' => $request->input('placeholder', $parametro->texto_ayuda),
            'help_text' => $request->input('help_text', $parametro->configuracion_adicional['help_text'] ?? null),
            'default_value' => $request->input('default_value', $parametro->configuracion_adicional['default_value'] ?? null),
            'required' => $request->input('required', $parametro->requerido ?? false),
            'options_source' => $request->has('options_source') ? $request->input('options_source') : ($parametro->fuente_opciones ? json_decode($parametro->fuente_opciones, true) : null),
            'validation' => $request->input('validation', $parametro->configuracion_adicional['validation'] ?? ($parametro->expresion_regular ? ['regex' => $parametro->expresion_regular] : null)),
            'order' => $request->input('order', $parametro->orden_defecto),
            'visibility_rule' => $request->input('visibility_rule', $parametro->regla_visibilidad ?? null)
        ];

        $parametro->update($norm);

        return response()->json([
            'success' => true,
            'data' => $this->mapPublic($parametro->fresh()),
            'message' => 'Parámetro creado exitosamente'
        ], 201);
    }

    public function show(ParametroFormulario $parametroFormulario): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->mapPublic($parametroFormulario),
            'message' => 'Parámetro encontrado'
        ]);
    }

    public function update(Request $request, ParametroFormulario $parametroFormulario): JsonResponse
    {
        // Map standardized keys to DB columns
        $input = $request->all();
        if (isset($input['name'])) $request->merge(['nombre' => $input['name']]);
        if (isset($input['label'])) $request->merge(['etiqueta' => $input['label']]);
        if (isset($input['data_type'])) $request->merge(['tipo_dato' => $input['data_type']]);
        if (isset($input['placeholder'])) $request->merge(['texto_ayuda' => $input['placeholder']]);
        if (isset($input['required'])) $request->merge(['requerido' => $input['required']]);
        if (isset($input['order'])) $request->merge(['orden_defecto' => $input['order']]);
        if (isset($input['validation']) && is_array($input['validation']) && isset($input['validation']['regex'])) {
            $request->merge(['expresion_regular' => $input['validation']['regex']]);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255|unique:parametro_formularios,nombre,' . $parametroFormulario->id,
            'etiqueta' => 'sometimes|string|max:255',
            'tipo_dato' => 'sometimes|in:' . implode(',', $this->tiposDatoPermitidos),
            'texto_ayuda' => 'nullable|string|max:255',
            'requerido' => 'boolean',
            'fuente_opciones' => 'nullable|string',
            'options_source' => 'nullable',
            'expresion_regular' => 'nullable|string',
            'mensaje_validacion' => 'nullable|string',
            'orden_defecto' => 'integer|min:0',
            'regla_visibilidad' => 'nullable|array',
            'configuracion_adicional' => 'nullable|array'
        ]);

        if ($request->has('options_source')) {
            $opts = $request->input('options_source');
            $validated['fuente_opciones'] = is_string($opts) ? $opts : json_encode($opts);
        }

        if ($request->has('visibility_rule')) {
            $validated['regla_visibilidad'] = $request->input('visibility_rule');
        }

        if ($request->has('help_text') || $request->has('default_value') || $request->has('validation')) {
            $conf = $validated['configuracion_adicional'] ?? ($parametroFormulario->configuracion_adicional ?? []);
            if ($request->has('help_text')) $conf['help_text'] = $request->input('help_text');
            if ($request->has('default_value')) $conf['default_value'] = $request->input('default_value');
            if ($request->has('validation')) $conf['validation'] = $request->input('validation');
            $validated['configuracion_adicional'] = $conf;
        }

        $parametroFormulario->update($validated);
        // También actualizar columnas normalizadas si es necesario
        $norm = [];
        if ($request->has('name')) $norm['name'] = $request->input('name');
        if ($request->has('label')) $norm['label'] = $request->input('label');
        if ($request->has('data_type')) $norm['data_type'] = $request->input('data_type');
        if ($request->has('placeholder')) $norm['placeholder'] = $request->input('placeholder');
        if ($request->has('help_text')) $norm['help_text'] = $request->input('help_text');
        if ($request->has('default_value')) $norm['default_value'] = $request->input('default_value');
        if ($request->has('required')) $norm['required'] = $request->input('required');
        if ($request->has('options_source')) $norm['options_source'] = $request->input('options_source');
        if ($request->has('validation')) $norm['validation'] = $request->input('validation');
        if ($request->has('order')) $norm['order'] = $request->input('order');
        if ($request->has('visibility_rule')) $norm['visibility_rule'] = $request->input('visibility_rule');

        if (!empty($norm)) {
            $parametroFormulario->update($norm);
        }

        return response()->json([
            'success' => true,
            'data' => $this->mapPublic($parametroFormulario->fresh()),
            'message' => 'Parámetro actualizado correctamente'
        ]);
    }

    public function destroy(ParametroFormulario $parametroFormulario): JsonResponse
    {
        $parametroFormulario->delete();

        return response()->json([
            'success' => true,
            'message' => 'Parámetro eliminado correctamente'
        ], 204);
    }

    private function mapPublic(ParametroFormulario $p): array
    {
        // Prefer columnas normalizadas si existen, si no caer atrás a legacy
        $options = null;
        try {
            if (!empty($p->options_source)) $options = is_string($p->options_source) ? json_decode($p->options_source, true) : $p->options_source;
            elseif (!empty($p->fuente_opciones)) $options = is_string($p->fuente_opciones) ? json_decode($p->fuente_opciones, true) : $p->fuente_opciones;
        } catch (\Throwable $e) { $options = null; }

        $validation = null;
        if (!empty($p->validation)) $validation = is_string($p->validation) ? json_decode($p->validation, true) : $p->validation;
        else {
            $conf = is_array($p->configuracion_adicional) ? $p->configuracion_adicional : (is_string($p->configuracion_adicional) ? @json_decode($p->configuracion_adicional, true) : null);
            if (!empty($conf['validation'])) $validation = $conf['validation'];
            elseif (!empty($p->expresion_regular)) $validation = ['regex' => $p->expresion_regular];
        }

        $help = $p->help_text ?? ($p->configuracion_adicional['help_text'] ?? null ?? null);
        $default = $p->default_value ?? ($p->configuracion_adicional['default_value'] ?? null ?? null);

        return [
            'id' => $p->id,
            'name' => $p->name ?? $p->nombre,
            'label' => $p->label ?? $p->etiqueta,
            'data_type' => $p->data_type ?? $p->tipo_dato,
            'default_value' => $default,
            'placeholder' => $p->placeholder ?? $p->texto_ayuda,
            'help_text' => $help,
            'required' => isset($p->required) ? (bool)$p->required : (bool)($p->requerido ?? false),
            'options_source' => $options,
            'validation' => $validation,
            'order' => $p->order ?? $p->orden_defecto,
            'visibility_rule' => $p->visibility_rule ?? $p->regla_visibilidad ?? null,
        ];
    }
}

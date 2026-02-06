<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParametroFormulario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ParametroFormularioController extends Controller
{
    private array $tiposDatoPermitidos = [
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
    ];

    public function index(Request $request): JsonResponse
    {
        $query = ParametroFormulario::query();

        if ($request->has('tipo_dato')) {
            $query->where('data_type', $request->tipo_dato);
        }

        if ($request->has('buscar')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', '%' . $request->buscar . '%')
                  ->orWhere('label', 'ilike', '%' . $request->buscar . '%');
            });
        }

        $query->orderBy('order');

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
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:parametro_formularios,name',
            'label' => 'required|string|max:255',
            'data_type' => 'required|in:' . implode(',', $this->tiposDatoPermitidos),
            'placeholder' => 'nullable|string|max:255',
            'required' => 'boolean',
            'options_source' => 'nullable|string',
            'validation_regex' => 'nullable|string',
            'order' => 'required|integer|min:0',
            'visibility_rule' => 'nullable|array'
        ]);

        $parametro = ParametroFormulario::create($validated);

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
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:parametro_formularios,name,' . $parametroFormulario->id,
            'label' => 'sometimes|string|max:255',
            'data_type' => 'sometimes|in:' . implode(',', $this->tiposDatoPermitidos),
            'placeholder' => 'nullable|string|max:255',
            'required' => 'boolean',
            'options_source' => 'nullable|string',
            'validation_regex' => 'nullable|string',
            'order' => 'integer|min:0',
            'visibility_rule' => 'nullable|array'
        ]);
        $parametroFormulario->update($validated);

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
        return [
            'id' => $p->id,
            'name' => $p->name,
            'label' => $p->label,
            'data_type' => $p->data_type,
            'placeholder' => $p->placeholder,
            'required' => (bool) $p->required,
            'options_source' => $p->options_source,
            'validation_regex' => $p->validation_regex,
            'order' => $p->order,
            'visibility_rule' => $p->visibility_rule,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoFormulario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TipoFormularioController extends Controller
{
    public function index(): JsonResponse
    {
        $tipos = TipoFormulario::where('activo', true)->get();
        
        return response()->json([
            'success' => true,
            'data' => $tipos,
            'message' => 'Tipos de formularios obtenidos correctamente'
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:tipo_formularios',
            'descripcion' => 'nullable|string',
            'metadatos' => 'nullable|array'
        ]);

        $validated['slug'] = \Str::slug($validated['nombre']);

        $tipoFormulario = TipoFormulario::create($validated);

        return response()->json([
            'success' => true,
            'data' => $tipoFormulario,
            'message' => 'Tipo de formulario creado exitosamente'
        ], 201);
    }

    public function show(TipoFormulario $tipoFormulario): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $tipoFormulario,
            'message' => 'Tipo de formulario encontrado'
        ]);
    }

    public function update(Request $request, TipoFormulario $tipoFormulario): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255|unique:tipo_formularios,nombre,' . $tipoFormulario->id,
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
            'metadatos' => 'nullable|array'
        ]);

        if (isset($validated['nombre'])) {
            $validated['slug'] = \Str::slug($validated['nombre']);
        }

        $tipoFormulario->update($validated);

        return response()->json([
            'success' => true,
            'data' => $tipoFormulario,
            'message' => 'Tipo de formulario actualizado correctamente'
        ]);
    }

    public function destroy(TipoFormulario $tipoFormulario): JsonResponse
    {
        $tipoFormulario->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tipo de formulario eliminado correctamente'
        ], 204);
    }
}

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TipoFormularioController;
use App\Http\Controllers\Api\ParametroFormularioController;
use App\Http\Controllers\Api\ConstructorFormularioController;

Route::apiResource('tipos-formulario', TipoFormularioController::class);
Route::apiResource('parametros-formulario', ParametroFormularioController::class);

Route::prefix('constructor')->group(function () {
    Route::post('/formulario/crear', [ConstructorFormularioController::class, 'crearFormulario']);
    Route::put('/formulario/{formulario}', [ConstructorFormularioController::class, 'updateFormulario']);
    Route::post('/formulario/{formulario}/clonar', [ConstructorFormularioController::class, 'clonarFormulario']);
    Route::get('/formulario/{formulario}/estructura', [ConstructorFormularioController::class, 'obtenerEstructura']);
    Route::get('/formularios', [ConstructorFormularioController::class, 'index']);
});

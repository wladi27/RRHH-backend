<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Formulario extends Model
{
    use SoftDeletes;

    protected $table = 'formularios';
    
    protected $fillable = [
        'tipo_formulario_id',
        'nombre',
        'version',
        'descripcion',
        'activo',
        'estructura',
        'metadatos'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'estructura' => 'array',
        'metadatos' => 'array'
    ];

    public function tipoFormulario(): BelongsTo
    {
        return $this->belongsTo(TipoFormulario::class, 'tipo_formulario_id');
    }

    public function parametros(): BelongsToMany
    {
        return $this->belongsToMany(
            ParametroFormulario::class,
            'formulario_parametro',
            'formulario_id',
            'parametro_formulario_id'
        )->withPivot('orden', 'requerido', 'configuracion_personalizada')
         ->orderByPivot('orden', 'asc');
    }

    public function cargos(): BelongsToMany
    {
        return $this->belongsToMany(Cargo::class, 'formulario_cargo');
    }

    public function clonar(string $nuevoNombre, string $nuevaVersion): ?self
    {
        try {
            $nuevoFormulario = $this->replicate();
            $nuevoFormulario->nombre = $nuevoNombre;
            $nuevoFormulario->version = $nuevaVersion;
            $nuevoFormulario->save();

            foreach ($this->parametros as $parametro) {
                $nuevoFormulario->parametros()->attach($parametro->id, [
                    'orden' => $parametro->pivot->orden,
                    'requerido' => $parametro->pivot->requerido,
                    'configuracion_personalizada' => $parametro->pivot->configuracion_personalizada
                ]);
            }

            foreach ($this->cargos as $cargo) {
                $nuevoFormulario->cargos()->attach($cargo->id);
            }

            return $nuevoFormulario->load(['parametros', 'cargos']);
            
        } catch (\Exception $e) {
            \Log::error('Error al clonar formulario: ' . $e->getMessage());
            return null;
        }
    }
}

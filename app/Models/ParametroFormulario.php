<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ParametroFormulario extends Model
{
    protected $table = 'parametro_formularios';
    
    protected $fillable = [
        'name',
        'label',
        'data_type',
        'placeholder',
        'required',
        'options_source',
        'validation_regex',
        'order',
        'visibility_rule'
    ];

    protected $casts = [
        'required' => 'boolean',
        'visibility_rule' => 'array'
    ];

    public function formularios(): BelongsToMany
    {
        return $this->belongsToMany(
            Formulario::class,
            'formulario_parametro',
            'parametro_formulario_id',
            'formulario_id'
        )->withPivot('orden', 'requerido', 'configuracion_personalizada');
    }
}

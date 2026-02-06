<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ParametroFormulario extends Model
{
    use SoftDeletes;

    protected $table = 'parametro_formularios';
    
    protected $fillable = [
        // legacy
        'nombre', 'etiqueta', 'tipo_dato', 'texto_ayuda', 'requerido', 'fuente_opciones', 'expresion_regular', 'mensaje_validacion', 'orden_defecto', 'regla_visibilidad', 'configuracion_adicional',
        // normalized
        'name', 'label', 'data_type', 'placeholder', 'help_text', 'required', 'options_source', 'validation', 'order', 'visibility_rule', 'default_value'
    ];

    protected $casts = [
        // legacy
        'requerido' => 'boolean',
        'regla_visibilidad' => 'array',
        'configuracion_adicional' => 'array',
        // normalized
        'required' => 'boolean',
        'options_source' => 'array',
        'validation' => 'array',
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoFormulario extends Model
{
    use SoftDeletes;

    protected $table = 'tipo_formularios';
    
    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'activo',
        'metadatos'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'metadatos' => 'array'
    ];

    public function formularios(): HasMany
    {
        return $this->hasMany(Formulario::class, 'tipo_formulario_id');
    }
}

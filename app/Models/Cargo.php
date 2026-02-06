<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cargo extends Model
{
    protected $table = 'cargos';
    
    protected $fillable = [
        'nombre',
        'codigo',
        'departamento',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    public function formularios(): BelongsToMany
    {
        return $this->belongsToMany(Formulario::class, 'formulario_cargo');
    }
}

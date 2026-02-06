<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cargo extends Model
{
    use SoftDeletes;

    protected $table = 'cargo';
    
    protected $fillable = [
        'id_nivel_jerarquico',
        'id_grado',
        'id_nivel_instruccion',
        'nombre',
        'codigo',
        'state'
    ];

    protected $casts = [
        'state' => 'boolean'
    ];

    public function formularios(): BelongsToMany
    {
        return $this->belongsToMany(Formulario::class, 'formulario_cargo');
    }
}

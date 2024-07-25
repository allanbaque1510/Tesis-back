<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoEstudiante extends Model
{
    use HasFactory;
    protected $table = 'grupo_estudiantes';
    protected $primaryKey = 'id_grupo';
    protected $fillable = [
        'codigo',
        'descripcion',
    ];
}

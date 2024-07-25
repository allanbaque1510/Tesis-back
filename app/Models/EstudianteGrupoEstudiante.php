<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstudianteGrupoEstudiante extends Model
{
    use HasFactory;
    protected $table = 'estudiante_grupo_estudiante';
    protected $primaryKey = 'id_estudiante_grupo';
    protected $fillable = [
        'id_periodo',
        'id_carrera',
        'id_materia',
        'id_grupo',
        'id_estudiante',
        'id_archivo',
    ];

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuntuacionGrupoEstudiante extends Model
{
    use HasFactory;
    protected $table = 'puntuacion_logro_grupo_estudiante';
    protected $primaryKey = 'id_puntuacion_estudiante';
    protected $fillable = [
        'id_logros_mat_carr',
        "pregunta",
        "puntuacion",
        "id_estudiante_grupo",
        'id_archivo',
    ];

}

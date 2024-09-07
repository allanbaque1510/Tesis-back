<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstudiantesReprobados extends Model
{
    use HasFactory;
    protected $table = 'estudiantes_reprobados';
    protected $primaryKey = 'id_estudiantes_reprobados ';
    protected $fillable = [
    'id_periodo',
    'id_carrera',
    'id_materia',
    'id_grupo',
    'id_estudiante',
    'asistencia',
    'promedio',
    'reprobado_asistencia',
    'reprobado_nota',
    'id_archivo',
    ];

}

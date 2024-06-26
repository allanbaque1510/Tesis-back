<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstudiantesEgresados extends Model
{
    use HasFactory;
    
    protected $table = 'estudiantes_egresados';
    protected $primaryKey = 'id_estudiantes_egresados ';
    protected $fillable = [
        'id_estudiante',
        'id_periodo',
        'id_periodo_relacionado',
        'id_carrera',
        'prom_materia',
        'prom_titulacion',
        'prom_general',
        'estado',
    ];
}

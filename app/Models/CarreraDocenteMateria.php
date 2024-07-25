<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarreraDocenteMateria extends Model
{
    use HasFactory;
    protected $table = 'carrera_docente_materias';
    protected $primaryKey = 'id_carrera_docente_materia';
    protected $fillable = [
        'id_periodo',
        'id_carrera',
        'id_materia',
        'id_grupo',
        'id_docente',
    ];

}

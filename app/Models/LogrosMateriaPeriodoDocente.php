<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogrosMateriaPeriodoDocente extends Model
{
    use HasFactory;
    protected $table = 'logros_mat_carr_per_doc';
    protected $primaryKey = 'id_logros_mat_carr_per_doc';
    protected $fillable = [
        'id_carrera_docente_materia',
        "id_logros",
    ];
    
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroEstudiantil extends Model
{
    use HasFactory;
    protected $table = 'registro_estudiantil';
    protected $primaryKey = 'id_registro';
    protected $fillable = [
        'id_estudiante',
        'id_periodo',
        'id_carrera',
        'id_habilitado',
        'nivel_actual',
        'nivel_anterior',
        'repetidor',
        'created_at',
        'updated_at',
    ];
}

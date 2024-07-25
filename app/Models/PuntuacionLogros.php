<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuntuacionLogros extends Model
{
    use HasFactory;
    protected $table = 'puntuacion_logros';
    protected $primaryKey = 'id_puntuacion_logros';
    protected $fillable = [
        'pregunta',
        'puntuacion',
        'id_archivo',
        'id_logros_mat_carr_per_doc',
    ];
}

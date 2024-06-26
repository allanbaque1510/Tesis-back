<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estudiantes extends Model
{
    use HasFactory;
    protected $table = 'estudiantes';
    protected $primaryKey = 'id';
    protected $fillable = [
        'ci',
        'estudiante',
        'telefono',
        'celular',
        'correo_personal',
        'correo_institucional',
    ];
}

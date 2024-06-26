<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Periodo extends Model
{
    use HasFactory;
    protected $table = 'periodo';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_codigo',
        'codigo',
        'anio_inicio',
        'anio_fin',
        'ciclo',
    ];
}

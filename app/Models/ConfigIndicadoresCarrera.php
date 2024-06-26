<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigIndicadoresCarrera extends Model
{
    use HasFactory;
    protected $table = 'config_indicadores_carreras';
    protected $primaryKey = 'id_configuracion';
    protected $fillable = [
        'periodos_desercion',
        'id_carrera',
        'total_periodos',
        'periodos_gracia',
    ];
}

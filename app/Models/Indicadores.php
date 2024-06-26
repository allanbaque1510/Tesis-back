<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Indicadores extends Model
{
    use HasFactory;
    protected $table = 'indicadores';
    protected $primaryKey = 'id_indicador';
    protected $fillable = [
        'descripcion'
    ];

}

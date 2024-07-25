<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogrosAprendizaje extends Model
{
    use HasFactory;
    protected $table = 'logros_aprendizaje';
    protected $primaryKey = 'id_logros';
    protected $fillable = [
        'codigo',
        'descripcion',
    ];
}

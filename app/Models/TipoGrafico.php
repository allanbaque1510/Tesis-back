<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoGrafico extends Model
{
    use HasFactory;
    protected $table = 'tipo_graficos';
    protected $primaryKey = 'id_tipo';
    protected $fillable = [
        'descripcion',
        'created_at',
        'updated_at',
    ];
}

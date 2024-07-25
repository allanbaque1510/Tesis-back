<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Docentes extends Model
{
    use HasFactory;
    protected $table = 'docentes';
    protected $primaryKey = 'id_docente';
    protected $fillable = [
        'nombre',
        'cedula',
    ];
}

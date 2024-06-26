<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArchivosSubidos extends Model
{
    use HasFactory;
    
    protected $table = 'archivos_subidos';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_periodo',
        'id_carrera',
        'id_indicador',
        'file_name',
        'file_hash',
        'file_path',
    ];
}

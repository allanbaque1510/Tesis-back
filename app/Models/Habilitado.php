<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Habilitado extends Model
{
    use HasFactory;
    
    protected $table = 'habilitado';
    protected $primaryKey = 'id';
    protected $fillable = [
        'descripcion',
    ];
}

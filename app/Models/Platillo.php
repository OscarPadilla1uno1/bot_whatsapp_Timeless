<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Platillo extends Model
{
    use HasFactory;

    protected $table = 'platillos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio_base',
        'imagen_url',
        'activo',
    ];

    public $timestamps = false; // si tu tabla no tiene columnas created_at / updated_at
}

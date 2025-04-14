<?php

// app/Models/Cliente.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $fillable = ['nombre', 'telefono'];
    
    // En Laravel, por defecto se esperan created_at y updated_at
    // Si tu tabla no los tiene, agrega esto:
    public $timestamps = false;
    
    // Relación con pedidos
    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }
}
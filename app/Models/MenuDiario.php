<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuDiario extends Model
{
    protected $table = 'menu_diario';
    protected $fillable = ['fecha', 'platillo_id', 'cantidad_disponible'];
    
    // Si tu tabla no tiene timestamps
    public $timestamps = false;
    
    // Relación con platillo
    public function platillo()
    {
        return $this->belongsTo(Platillo::class);
    }
}
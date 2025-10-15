<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EnvioGratisFecha extends Model
{
    protected $table = 'envios_gratis_fechas';

    protected $fillable = ['fecha', 'activo', 'cantidad_minima'];

    public static function tieneEnvioGratisParaFecha(Carbon $fecha)
    {
        return self::where('fecha', $fecha->toDateString())->where('activo', true)->exists();
    }

    public static function establecerSiNoExiste(Carbon $fecha)
    {
        $yaExiste = self::where('fecha', $fecha->toDateString())->exists();
        if (!$yaExiste) {
            self::create([
                'fecha' => $fecha->toDateString(),
                'activo' => $fecha->isSaturday(),
                'cantidad_minima' => 3,
            ]);
        }
    }
}

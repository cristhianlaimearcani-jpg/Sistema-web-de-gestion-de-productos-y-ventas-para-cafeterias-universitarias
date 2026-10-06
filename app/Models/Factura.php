<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $fillable = [
        'numero', 'pedido_id', 'razon_social', 'nit_ci',
        'total', 'codigo_verificacion', 'emitida_en',
    ];

    protected function casts(): array
    {
        return [
            'total'      => 'decimal:2',
            'emitida_en' => 'datetime',
        ];
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function getNumeroFormateadoAttribute(): string
    {
        return str_pad((string) $this->numero, 6, '0', STR_PAD_LEFT);
    }

    // Emite la factura de un pedido pagado (una sola por pedido)
    public static function emitir(Pedido $pedido): self
    {
        $existente = static::where('pedido_id', $pedido->id)->first();

        if ($existente) {
            return $existente;
        }

        $numero  = ((int) static::max('numero')) + 1;
        $emitida = now();

        $hash = strtoupper(substr(
            hash('sha256', $numero . '|' . $pedido->id . '|' . $pedido->total . '|' . $emitida->timestamp . '|' . config('app.key')),
            0,
            16
        ));

        return static::create([
            'numero'              => $numero,
            'pedido_id'           => $pedido->id,
            'razon_social'        => $pedido->razon_social ?: $pedido->user->name,
            'nit_ci'              => $pedido->nit_ci ?: '0',
            'total'               => $pedido->total,
            'codigo_verificacion' => implode('-', str_split($hash, 4)),
            'emitida_en'          => $emitida,
        ]);
    }
}
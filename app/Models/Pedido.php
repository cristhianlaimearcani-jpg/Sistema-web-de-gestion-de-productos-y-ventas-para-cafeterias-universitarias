<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $fillable = [
        'user_id', 'total', 'estado', 'codigo_recojo', 'hora_recojo',
        'razon_social', 'nit_ci', 'pagado_en',
    ];

    protected function casts(): array
    {
        return [
            'total'     => 'decimal:2',
            'pagado_en' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    public function factura()
    {
        return $this->hasOne(Factura::class);
    }

    // HU-09: el total se calcula automáticamente con los productos del pedido
    public function recalcularTotal(): void
    {
        $suma = $this->detalles()->get()->sum(
            fn ($detalle) => $detalle->cantidad * $detalle->precio_unitario
        );

        $this->total = round($suma, 2);
        $this->save();

        // Si el pedido cambió, el QR que estaba pendiente ya no corresponde al monto
        $this->pagos()->where('estado', 'pendiente')->update([
            'estado'  => 'fallido',
            'detalle' => 'Modificaste el pedido. Genera un nuevo código QR.',
        ]);
    }

    // Mientras el pago se verifica o falta escribir el código, el pedido no se puede cambiar
    public function tienePagoEnProceso(): bool
    {
        return $this->pagos()->whereIn('estado', ['por_verificar', 'recibido'])->exists();
    }

    // El pago que sigue activo (si lo hay)
    public function pagoActivo(): ?Pago
    {
        return $this->pagos()
            ->whereIn('estado', ['pendiente', 'por_verificar', 'recibido'])
            ->latest('id')
            ->first();
    }

    // HU-12: código de recojo único (sin letras confusas como O, 0, I, 1)
    public static function generarCodigoUnico(): string
    {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $codigo = '';
            for ($i = 0; $i < 6; $i++) {
                $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }
        } while (static::where('codigo_recojo', $codigo)->exists());

        return $codigo;
    }

    public function getEstadoTextoAttribute(): string
    {
        return match ($this->estado) {
            'pendiente'      => 'Pendiente de pago',
            'pagado'         => 'Pagado',
            'en_preparacion' => 'En preparación',
            'listo'          => 'Listo para recoger',
            'entregado'      => 'Entregado',
            'cancelado'      => 'Cancelado',
            default          => ucfirst($this->estado),
        };
    }
}
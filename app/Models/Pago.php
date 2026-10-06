<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Pago extends Model
{
    protected $fillable = [
        'pedido_id', 'monto', 'metodo', 'estado', 'referencia', 'comprobante',
        'expira_en', 'recibido_en', 'pagado_en', 'detalle',
    ];

    protected function casts(): array
    {
        return [
            'monto'       => 'decimal:2',
            'expira_en'   => 'datetime',
            'recibido_en' => 'datetime',
            'pagado_en'   => 'datetime',
        ];
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function verificaciones()
    {
        return $this->hasMany(Verificacion::class);
    }

    // Crea un intento de pago (o reutiliza el que sigue activo)
    public static function iniciarPara(Pedido $pedido): self
    {
        foreach ($pedido->pagos()->where('estado', 'pendiente')->get() as $anterior) {
            if ($anterior->expira_en && $anterior->expira_en->isPast()) {
                $anterior->fallar('El tiempo para pagar venció.');
            } elseif ((float) $anterior->monto !== (float) $pedido->total) {
                $anterior->fallar('El monto del pedido cambió.');
            }
        }

        $activo = $pedido->pagoActivo();

        if ($activo) {
            return $activo;
        }

        return $pedido->pagos()->create([
            'monto'      => $pedido->total,
            'metodo'     => config('cafeqr.pago_modo') === 'banco' ? 'banco' : 'demo',
            'estado'     => 'pendiente',
            'referencia' => Str::random(40),
            'expira_en'  => now()->addMinutes((int) config('cafeqr.pago_minutos', 30)),
        ]);
    }

    public function refrescarVencimiento(): void
    {
        if ($this->estado === 'pendiente' && $this->expira_en && $this->expira_en->isPast()) {
            $this->update([
                'estado'  => 'fallido',
                'detalle' => 'El tiempo para pagar venció.',
            ]);
        }
    }

    // Solo se puede marcar como fallido un pago que todavía no llegó
    public function fallar(string $motivo): void
    {
        if (in_array($this->estado, ['pendiente', 'por_verificar'], true)) {
            $this->update(['estado' => 'fallido', 'detalle' => $motivo]);
        }
    }

    // El estudiante dice "ya pagué" (modo banco): queda esperando que la cafetería lo confirme
    public function marcarPorVerificar(?string $comprobante): string
    {
        $this->refrescarVencimiento();

        if ($this->estado !== 'pendiente') {
            return $this->estado;
        }

        $this->update([
            'estado'      => 'por_verificar',
            'comprobante' => $comprobante ? trim($comprobante) : null,
        ]);

        return 'por_verificar';
    }

    // El dinero llegó. Devuelve: recibido | ya_recibido | fallido | vencido | pedido_cerrado
    // Después de esto hay que llamar a enviarCodigo() para mandar el código al correo.
    public function marcarRecibido(): string
    {
        return DB::transaction(function () {
            $pago = static::query()->whereKey($this->id)->lockForUpdate()->first();

            if (in_array($pago->estado, ['recibido', 'pagado'], true)) {
                return 'ya_recibido';
            }

            if ($pago->estado === 'fallido') {
                return 'fallido';
            }

            if ($pago->estado === 'pendiente' && $pago->expira_en && $pago->expira_en->isPast()) {
                $pago->update(['estado' => 'fallido', 'detalle' => 'El tiempo para pagar venció.']);

                return 'vencido';
            }

            if ($pago->pedido->estado !== 'pendiente') {
                return 'pedido_cerrado';
            }

            $pago->update(['estado' => 'recibido', 'recibido_en' => now()]);

            $this->refresh();

            return 'recibido';
        });
    }

    // Envía al correo del estudiante el código de 6 dígitos para confirmar
    public function enviarCodigo(): bool
    {
        try {
            Verificacion::enviarA($this);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    // Se llama cuando el estudiante escribió bien el código:
    // pedido pagado + código de recojo + factura. Devuelve: completado | ya_pagado | no_recibido
    public function completar(): string
    {
        return DB::transaction(function () {
            $pago = static::query()->whereKey($this->id)->lockForUpdate()->first();
            $pedido = $pago->pedido;

            if ($pago->estado === 'pagado') {
                return 'ya_pagado';
            }

            if ($pago->estado !== 'recibido') {
                return 'no_recibido';
            }

            $pago->update(['estado' => 'pagado', 'pagado_en' => now()]);

            $pedido->update([
                'estado'        => 'pagado',
                'pagado_en'     => now(),
                'codigo_recojo' => Pedido::generarCodigoUnico(),
            ]);

            Factura::emitir($pedido);

            // Los demás intentos de este pedido quedan anulados
            $pedido->pagos()
                ->whereIn('estado', ['pendiente', 'por_verificar'])
                ->where('id', '!=', $pago->id)
                ->update(['estado' => 'fallido', 'detalle' => 'El pedido se pagó con otro intento.']);

            $this->refresh();

            return 'completado';
        });
    }
}
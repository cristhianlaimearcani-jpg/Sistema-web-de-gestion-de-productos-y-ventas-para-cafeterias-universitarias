<?php

namespace App\Models;

use App\Services\Notificador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Pago extends Model
{
    public const METODO_QR = 'qr';
    public const METODO_EFECTIVO = 'efectivo';

    /** Pagos que todavía no terminaron. */
    public const ABIERTOS = ['pendiente', 'por_verificar', 'recibido', 'por_cobrar'];

    /** Resultados de completar(). */
    public const OK = 'ok';
    public const YA_PAGADO = 'ya_pagado';
    public const NO_DISPONIBLE = 'no_disponible';
    public const TRANSACCION_REPETIDA = 'transaccion_repetida';

    protected $fillable = ['pedido_id', 'metodo', 'referencia', 'monto', 'estado', 'vence_en', 'transaccion', 'confirmado_en', 'confirmado_por'];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2', 'vence_en' => 'datetime', 'confirmado_en' => 'datetime'];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function esEfectivo(): bool
    {
        return $this->metodo === self::METODO_EFECTIVO;
    }

    /** Crea un pago nuevo y cierra los anteriores que seguían abiertos. */
    public static function iniciarPara(Pedido $pedido, string $metodo): self
    {
        $pedido->pagos()->whereIn('estado', self::ABIERTOS)->update(['estado' => 'cancelado']);

        $efectivo = $metodo === self::METODO_EFECTIVO;

        return self::create([
            'pedido_id' => $pedido->id,
            'metodo' => $metodo,
            'referencia' => Str::upper(Str::random(24)),
            'monto' => $pedido->total,
            'estado' => $efectivo ? 'por_cobrar' : 'pendiente',
            'vence_en' => $efectivo ? null : now()->addMinutes(max(1, (int) config('cafeqr.pago_minutos'))),
        ]);
    }

    public function estaVencido(): bool
    {
        return $this->estado === 'pendiente' && $this->vence_en && $this->vence_en->isPast();
    }

    /** Si el QR venció sin pagarse, lo marca como fallido. */
    public function refrescarVencimiento(): void
    {
        if ($this->estaVencido()) {
            $this->update(['estado' => 'fallido']);
        }
    }

    public function marcarPorVerificar(): void
    {
        if ($this->estado === 'pendiente') {
            $this->update(['estado' => 'por_verificar']);
        }
    }

    public function rechazar(): void
    {
        if (in_array($this->estado, self::ABIERTOS, true)) {
            $this->update(['estado' => 'fallido']);
        }
    }

    /**
     * Termina el pago: el pedido queda pagado, con código de recojo y factura.
     * Es seguro llamarlo dos veces: solo la primera acredita el pago.
     *  - $por: celular | simulado | caja
     *  - $transaccion: n.º del comprobante del banco (si se repite en otro pago, no se acredita).
     */
    public function completar(string $por = 'caja', ?string $transaccion = null): string
    {
        $transaccion = $transaccion !== null ? Str::upper(trim($transaccion)) : null;
        $transaccion = $transaccion === '' ? null : $transaccion;

        if ($transaccion) {
            $otro = self::where('transaccion', $transaccion)->where('id', '!=', $this->id)->first();
            if ($otro) {
                return self::TRANSACCION_REPETIDA;
            }
        }

        $pedido = $this->pedido;

        try {
            $resultado = DB::transaction(function () use ($pedido, $por, $transaccion) {
                // Solo un pago abierto puede pasar a completado (si llega dos veces, la segunda no hace nada).
                $tomado = self::whereKey($this->id)->whereIn('estado', self::ABIERTOS)->update([
                    'estado' => 'completado',
                    'confirmado_en' => now(),
                    'confirmado_por' => $por,
                    'transaccion' => $transaccion ?? 'CQR-'.$this->referencia,
                ]);
                if (! $tomado) {
                    return $pedido->fresh()->pagado_en ? self::YA_PAGADO : self::NO_DISPONIBLE;
                }

                $pagado = Pedido::whereKey($pedido->id)->where('estado', Pedido::PENDIENTE_PAGO)->update([
                    'estado' => Pedido::PAGADO,
                    'pagado_en' => now(),
                    'metodo_pago' => $this->metodo,
                    'codigo' => $pedido->codigo ?: Pedido::generarCodigoUnico(),
                ]);
                if (! $pagado) {
                    throw new \RuntimeException(self::NO_DISPONIBLE);
                }

                Factura::emitir($pedido->fresh());

                return self::OK;
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return self::TRANSACCION_REPETIDA;
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== self::NO_DISPONIBLE) {
                throw $e;
            }

            return self::NO_DISPONIBLE;
        }

        $this->refresh();

        if ($resultado === self::OK) {
            Notificador::pagoConfirmado($pedido->fresh(['user', 'factura', 'detalles']));
        }

        return $resultado;
    }
}

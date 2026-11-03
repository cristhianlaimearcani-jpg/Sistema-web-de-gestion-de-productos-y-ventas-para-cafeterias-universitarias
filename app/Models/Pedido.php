<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Pedido extends Model
{
    public const CARRITO = 'carrito';
    public const PENDIENTE_PAGO = 'pendiente_pago';
    public const PAGADO = 'pagado';
    public const EN_PREPARACION = 'en_preparacion';
    public const LISTO = 'listo';
    public const ENTREGADO = 'entregado';
    public const CANCELADO = 'cancelado';

    /** Pedidos que la cafetería debe atender. */
    public const ACTIVOS = [self::PAGADO, self::EN_PREPARACION, self::LISTO];

    public const TEXTOS = [
        self::CARRITO => 'Armando pedido',
        self::PENDIENTE_PAGO => 'Pendiente de pago',
        self::PAGADO => 'Pagado',
        self::EN_PREPARACION => 'En preparación',
        self::LISTO => 'Listo para recoger',
        self::ENTREGADO => 'Entregado',
        self::CANCELADO => 'Cancelado',
    ];

    protected $fillable = [
        'user_id', 'estado', 'total', 'cupon', 'descuento', 'codigo', 'metodo_pago', 'razon_social', 'nit_ci',
        'tiempo_estimado', 'preparacion_inicio', 'pagado_en', 'listo_en', 'entregado_en',
        'cancelado_por', 'motivo_cancelacion', 'cancelado_en', 'reembolso_pendiente', 'reembolsado_en',
        'clave_intento', 'stock_reservado',
    ];

    /** Grupos que ve el cliente en "Mis pedidos". */
    public const GRUPOS = [
        'pendientes' => ['texto' => 'Pendientes', 'estados' => [self::PENDIENTE_PAGO, self::PAGADO]],
        'en_preparacion' => ['texto' => 'En preparación', 'estados' => [self::EN_PREPARACION]],
        'listos' => ['texto' => 'Listos', 'estados' => [self::LISTO]],
        'entregados' => ['texto' => 'Entregados', 'estados' => [self::ENTREGADO]],
        'cancelados' => ['texto' => 'Cancelados', 'estados' => [self::CANCELADO]],
    ];

    /** Un pedido igual en este rango de horas se considera "reciente". */
    public const HORAS_REPETIDO = 3;

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'descuento' => 'decimal:2',
            'tiempo_estimado' => 'integer',
            'preparacion_inicio' => 'datetime',
            'pagado_en' => 'datetime',
            'listo_en' => 'datetime',
            'entregado_en' => 'datetime',
            'cancelado_en' => 'datetime',
            'reembolsado_en' => 'datetime',
            'reembolso_pendiente' => 'boolean',
            'stock_reservado' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function factura(): HasOne
    {
        return $this->hasOne(Factura::class);
    }

    /** Suma de los productos, antes de aplicar cupones. */
    public function subtotalProductos(): float
    {
        return round($this->detalles()->get()->sum(fn ($d) => (float) $d->precio_unitario * (int) $d->cantidad), 2);
    }

    /**
     * Total = productos − descuento del cupón. El cupón solo vale si el pedido es mayor
     * que su monto (el total nunca queda en cero); si deja de cumplirse, se quita solo.
     */
    public function recalcularTotal(): void
    {
        $subtotal = $this->subtotalProductos();
        $descuento = 0.0;
        $cupon = $this->cupon;

        if ($cupon) {
            $registro = Cupon::buscar($cupon);
            if ($registro && $subtotal > (float) $registro->monto) {
                $descuento = (float) $registro->monto;
            } else {
                $cupon = null;
            }
        }

        $this->update(['total' => round($subtotal - $descuento, 2), 'cupon' => $cupon, 'descuento' => $descuento]);
    }

    public function getEstadoTextoAttribute(): string
    {
        return self::TEXTOS[$this->estado] ?? ucfirst(str_replace('_', ' ', (string) $this->estado));
    }

    public function getCantidadItemsAttribute(): int
    {
        return (int) $this->detalles->sum('cantidad');
    }

    /** Momento en que se espera que esté listo (si ya está en preparación). */
    public function getListoEstimadoAttribute(): ?Carbon
    {
        if (! $this->preparacion_inicio || ! $this->tiempo_estimado) {
            return null;
        }

        return $this->preparacion_inicio->copy()->addMinutes($this->tiempo_estimado);
    }

    /** Minutos que faltan (0 si ya pasó). Null si todavía no hay tiempo estimado. */
    public function minutosRestantes(): ?int
    {
        $fin = $this->listo_estimado;
        if (! $fin || $this->estado !== self::EN_PREPARACION) {
            return null;
        }

        return max(0, (int) ceil(now()->diffInSeconds($fin, false) / 60));
    }

    public function pagoActivo(): ?Pago
    {
        return $this->pagos()->whereIn('estado', Pago::ABIERTOS)->latest('id')->first();
    }

    /** El cliente puede cancelar mientras no se haya empezado a preparar. */
    public function puedeCancelarCliente(): bool
    {
        return in_array($this->estado, [self::PENDIENTE_PAGO, self::PAGADO], true);
    }

    public function puedeCancelarPersonal(): bool
    {
        return in_array($this->estado, [self::PENDIENTE_PAGO, ...self::ACTIVOS], true);
    }

    /** $por: 'cliente' o 'personal'. Si ya había dinero recibido, queda un reembolso pendiente. */
    public function cancelar(string $por, ?string $motivo = null): void
    {
        $habiaDinero = $this->pagado_en !== null || $this->pagos()->whereIn('estado', ['recibido', 'completado'])->exists();

        $this->pagos()->whereIn('estado', Pago::ABIERTOS)->update(['estado' => 'cancelado']);

        // Si todavía no se empezó a preparar, las unidades vuelven al stock.
        if (in_array($this->estado, [self::PENDIENTE_PAGO, self::PAGADO], true)) {
            $this->devolverStock();
        }

        $this->update([
            'estado' => self::CANCELADO,
            'cancelado_por' => $por,
            'motivo_cancelacion' => $motivo ? Str::limit(trim($motivo), 250, '') : null,
            'cancelado_en' => now(),
            'reembolso_pendiente' => $habiaDinero,
        ]);
    }

    /* ------------------------------ Stock ------------------------------ */

    /**
     * Descuenta del stock las unidades del pedido. Devuelve null si todo salió bien
     * o un mensaje si algún producto ya no alcanza (en ese caso no descuenta nada).
     */
    public function reservarStock(): ?string
    {
        if ($this->stock_reservado) {
            return null;
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () {
                // Se "toma" la reserva primero: si dos envíos llegan juntos, solo uno descuenta.
                $tomado = self::whereKey($this->id)->where('stock_reservado', false)->update(['stock_reservado' => true]);
                if (! $tomado) {
                    return;
                }
                foreach ($this->detalles()->get() as $detalle) {
                    if (! $detalle->producto_id) {
                        continue;
                    }
                    $hecho = Producto::where('id', $detalle->producto_id)
                        ->where('stock', '>=', $detalle->cantidad)
                        ->decrement('stock', $detalle->cantidad);
                    if (! $hecho) {
                        $quedan = (int) Producto::whereKey($detalle->producto_id)->value('stock');
                        throw new \DomainException($quedan > 0
                            ? 'Solo quedan '.$quedan.' unidades de '.$detalle->nombre.'. Cambia la cantidad para continuar.'
                            : $detalle->nombre.' se agotó. Quítalo de tu pedido para continuar.');
                    }
                }
            });
        } catch (\DomainException $e) {
            return $e->getMessage();
        }

        $this->stock_reservado = (bool) self::whereKey($this->id)->value('stock_reservado');

        return null;
    }

    /** Devuelve al stock las unidades reservadas (una sola vez). */
    public function devolverStock(): void
    {
        if (! $this->stock_reservado) {
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () {
            $cambiados = self::whereKey($this->id)->where('stock_reservado', true)->update(['stock_reservado' => false]);
            if (! $cambiados) {
                return;
            }
            foreach ($this->detalles()->get() as $detalle) {
                if ($detalle->producto_id) {
                    Producto::whereKey($detalle->producto_id)->increment('stock', $detalle->cantidad);
                }
            }
        });
        $this->stock_reservado = false;
    }

    /* ------------------------------ Pedidos repetidos ------------------------------ */

    /** "3:1|7:2" → mismos productos con las mismas cantidades. */
    public function firma(): string
    {
        return $this->detalles()->get()
            ->groupBy('producto_id')
            ->map(fn ($g, $id) => $id.':'.$g->sum('cantidad'))
            ->sort()->values()->implode('|');
    }

    /** Otro pedido reciente del mismo cliente, sin terminar, con exactamente los mismos productos. */
    public function igualReciente(): ?self
    {
        $firma = $this->firma();
        if ($firma === '') {
            return null;
        }

        return self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->whereIn('estado', [self::PENDIENTE_PAGO, self::PAGADO, self::EN_PREPARACION, self::LISTO])
            ->where('created_at', '>=', now()->subHours(self::HORAS_REPETIDO))
            ->latest('id')->get()
            ->first(fn (self $p) => $p->firma() === $firma);
    }

    /** Grupo de "Mis pedidos" al que pertenece (pendientes, en_preparacion, ...). */
    public function getGrupoAttribute(): ?string
    {
        foreach (self::GRUPOS as $clave => $grupo) {
            if (in_array($this->estado, $grupo['estados'], true)) {
                return $clave;
            }
        }

        return null;
    }

    /**
     * Cancela los pedidos que se quedaron "Pendiente de pago" sin movimiento (el cliente se fue)
     * y devuelve sus unidades al stock. No toca los que la caja tiene que verificar.
     * Devuelve cuántos canceló.
     */
    public static function cancelarAbandonados(): int
    {
        $minutos = max(15, (int) config('cafeqr.minutos_sin_pagar', 90));
        $limite = now()->subMinutes($minutos);

        $pedidos = self::where('estado', self::PENDIENTE_PAGO)
            ->where('updated_at', '<', $limite)
            ->whereDoesntHave('pagos', fn ($q) => $q->whereIn('estado', ['por_verificar', 'recibido', 'completado']))
            ->whereDoesntHave('pagos', fn ($q) => $q->where('updated_at', '>=', $limite))
            ->with('user')
            ->limit(50)
            ->get();

        foreach ($pedidos as $pedido) {
            $pedido->cancelar('sistema', 'No se completó el pago a tiempo.');
            \App\Services\Notificador::cancelado($pedido->fresh(['user', 'detalles']));
        }

        return $pedidos->count();
    }

    /** Código de recojo de 5 caracteres, sin letras que se confunden (O/0, I/1). */
    public static function generarCodigoUnico(): string
    {
        $letras = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $codigo = '';
            for ($i = 0; $i < 5; $i++) {
                $codigo .= $letras[random_int(0, strlen($letras) - 1)];
            }
        } while (self::where('codigo', $codigo)->exists());

        return $codigo;
    }
}

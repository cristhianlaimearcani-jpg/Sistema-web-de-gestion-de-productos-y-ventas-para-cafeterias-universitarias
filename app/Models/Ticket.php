<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Consulta de soporte: un cliente cuenta un problema y el personal le responde. */
class Ticket extends Model
{
    public const TIPOS = [
        'pago' => 'Problema con un pago',
        'pedido' => 'Problema con un pedido',
        'error' => 'Error en la página',
        'cuenta' => 'Mi cuenta',
        'otro' => 'Otra consulta',
    ];

    public const ABIERTO = 'abierto';
    public const EN_PROCESO = 'en_proceso';
    public const RESUELTO = 'resuelto';

    public const ESTADOS = [
        self::ABIERTO => 'Abierto',
        self::EN_PROCESO => 'En revisión',
        self::RESUELTO => 'Resuelto',
    ];

    protected $fillable = ['user_id', 'pedido_id', 'tipo', 'asunto', 'estado', 'resuelto_en'];

    protected function casts(): array
    {
        return ['resuelto_en' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(TicketMensaje::class)->oldest('id');
    }

    public function getTipoTextoAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? 'Consulta';
    }

    public function getEstadoTextoAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }

    /** Tickets que el personal todavía debe atender. */
    public static function pendientes(): int
    {
        return self::whereIn('estado', [self::ABIERTO, self::EN_PROCESO])->count();
    }
}

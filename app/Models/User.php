<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'telefono',
        'foto',
        'rol',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }

    // El pedido que el usuario está armando (todavía sin pagar)
    public function pedidoPendiente(): ?Pedido
    {
        return $this->pedidos()
            ->where('estado', 'pendiente')
            ->with('detalles.producto')
            ->latest('id')
            ->first();
    }

    // Iniciales para el avatar cuando no hay foto (ej. "Jhon Barros" -> "JB")
    public function getInicialesAttribute(): string
    {
        $partes = preg_split('/\s+/', trim($this->name));
        $ini = mb_substr($partes[0] ?? '', 0, 1);

        if (count($partes) > 1) {
            $ini .= mb_substr(end($partes), 0, 1);
        }

        return mb_strtoupper($ini);
    }
}
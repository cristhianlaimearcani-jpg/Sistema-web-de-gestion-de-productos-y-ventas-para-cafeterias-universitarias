<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketMensaje extends Model
{
    protected $table = 'ticket_mensajes';

    protected $fillable = ['ticket_id', 'user_id', 'es_personal', 'mensaje'];

    protected function casts(): array
    {
        return ['es_personal' => 'boolean'];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

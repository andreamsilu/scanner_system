<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppInvitation extends Model
{
    protected $table = 'whatsapp_invitations';

    protected $guarded = ['id'];

    protected $casts = ['attempted_at' => 'datetime'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}

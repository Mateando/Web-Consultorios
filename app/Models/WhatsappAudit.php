<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Appointment;
use App\Models\User;

class WhatsappAudit extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_audits';

    protected $fillable = [
        'appointment_id',
        'user_id',
        'recipient_phone',
        'message',
        'status',
        'meta',
        'sent_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'sent_at' => 'datetime',
    ];

    public function appointment()
    {
    return $this->belongsTo(Appointment::class);
    }

    public function user()
    {
    return $this->belongsTo(User::class);
    }
}

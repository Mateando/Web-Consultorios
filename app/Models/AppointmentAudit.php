<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentAudit extends Model
{
    use HasFactory;

    protected $table = 'appointment_audits';

    protected $fillable = [
        'appointment_id',
        'user_id',
        'action',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];
}

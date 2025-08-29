<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicSetting extends Model
{
    use HasFactory;

    protected $table = 'clinic_settings';

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'tax_id',
    'footer_notes',
    'logo_path',
    ];
}

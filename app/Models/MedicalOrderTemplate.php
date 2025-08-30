<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalOrderTemplate extends Model
{
    protected $fillable = [
        'title',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];
}

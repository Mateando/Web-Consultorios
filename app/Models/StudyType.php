<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'cost', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'cost' => 'decimal:2',
    ];
}

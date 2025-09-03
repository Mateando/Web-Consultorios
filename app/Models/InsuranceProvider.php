<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','is_active'
    ];

    public function doctors()
    {
        return $this->belongsToMany(\App\Models\Doctor::class, 'doctor_insurance_provider');
    }
}

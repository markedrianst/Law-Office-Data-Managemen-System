<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourtOffice extends Model
{
    use HasFactory;

    protected $table = 'courts';

    protected $fillable = [
        'name', 'type', 'address', 'sort_order','is_active', 'contact_info', 
    ];

    protected $casts = [
        'is_active'  => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name',    'like', "%{$search}%")
              ->orWhere('address', 'like', "%{$search}%");
        });
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderMovement extends Model
{
    protected $table = 'folder_tracker';

    protected $fillable = [
        'case_id',
        'recorded_by',
        'type',
        'from_to',
        'date',
        'purpose',
        'handled_by',
        'is_current',
    ];

    protected $casts = [
        'date'       => 'date',
        'is_current' => 'boolean',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(Cases::class, 'case_id');
    }
    
    public function cases(): BelongsTo
{
    return $this->belongsTo(Cases::class, 'case_id');
}



    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cases extends Model
{
    protected $fillable = [
        'case_code',
        'case_no',
        'title',
        'category_id',
        'client_id',
        'court_or_office',
        'docket_no',
        'assigned_lawyer_id',
        'assigned_clerk_id',
        'priority',
        'intake_status',
        'case_status',
        'summary',
        'created_by',
    ];

    public function category()
    {
        return $this->belongsTo(CaseCategory::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedLawyer()
    {
        return $this->belongsTo(User::class, 'assigned_lawyer_id');
    }

    public function assignedClerk()
    {
        return $this->belongsTo(User::class, 'assigned_clerk_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(CaseActivityLog::class);
    }
}
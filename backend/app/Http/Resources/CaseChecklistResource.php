<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseChecklistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'case_id'     => $this->case_id,
            'task'        => $this->task,
            'status'      => $this->status,
            'due_date'    => $this->due_date?->format('Y-m-d'),
            'assigned_to' => $this->assigned_to,
            'notes'       => $this->notes,
            'completed_at'=> $this->completed_at?->toIso8601String(),
            'created_by'  => $this->created_by,
            'created_at'  => $this->created_at->toIso8601String(),
            'updated_at'  => $this->updated_at->toIso8601String(),
        ];
    }
}

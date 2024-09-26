<?php

// create segment resource
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class StudyZoneResource extends JsonResource
{
    public function toArray(Request $request)
    {

        if (is_null($this->resource)) {
            return [];
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'description' => $this->description,
            'conclusion' => $this->conclusion,
            'boundaries' => $this->coordinates,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_active' => $this->is_active,
            'relationships' => [
                'documents' => $this->documents,
                'collaborators' => $this->collaborators,
            ],
        ];
    }

}

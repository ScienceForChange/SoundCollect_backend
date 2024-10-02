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
            'start_date' => (new \Carbon\Carbon($this->start_date))->format('Y-m-d H:i:s'),
            'end_date' => (new \Carbon\Carbon($this->end_date))->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_visible' => $this->is_visible,
            'relationships' => [
                'documents' => $this->documents,
                'collaborators' => $this->collaborators,
            ],
        ];
    }

}

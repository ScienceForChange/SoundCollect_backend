<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (is_null($this->resource)) {
            return [];
        }

        return [
            'type' => $this->getProfileTypeAttribute(),
            'id' => $this->id,
            'attributes' => [
                'email' => $this->when($request->user()?->id === $this->id, $this->email),
                'avatar_id' => $this->avatar_id,
                'profile' => new ProfileCitizenResource($this->profile),
                // Agrego esta comprobación para evitar cargar este dato innecesariamenta ya que debería cambiarse la forma en que se calcula (issue #30)
                'level' => $this->when($request->has('with-levels'), $this->resource->calculatedLevel()),
                'created_at' => $this->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            ],
            'relationships' => [
                'observations' => ObservationResource::collection($this->whenLoaded('observations')),
            ],
        ];
    }
}

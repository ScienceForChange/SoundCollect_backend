<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserObservationsResource extends JsonResource
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
            'type' => 'observation',
            'id' => $this->id,
            'attributes' => [
                'Leq' => $this->Leq,
                'LAeqT' => $this->LAeqT,
                'LAmax' => $this->LAmax,
                'LAmin' => $this->LAmin,
                'L90' => $this->L90,
                'L10' => $this->L10,
                'sharpness_S' => 'N/A',
                'loudness_N' => 'N/A',
                'roughtness_R' => 'N/A',
                'fluctuation_strength_F' => 'N/A',
                'images' => $this->images,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'quiet' => $this->quiet,
                'cleanliness' => $this->cleanliness,
                'accessibility' => $this->accessibility,
                'safety' => $this->safety,
                'influence' => $this->influence,
                'landmark' => $this->landmark,
                'protection' => $this->protection,
                'wind_speed' => $this->wind_speed,
                'humidity' => $this->humidity,
                'temperature' => $this->temperature,
                'pressure' => $this->pressure,
                'user_id' => $this->user_id,
                'created_at' => $this->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
                'user_local_time' => $this->user_local_time,
            ],
            'relationships' => [
                'types' => TypeResource::collection($this->types),
            ],
        ];
    }
}

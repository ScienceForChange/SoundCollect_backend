<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ObservationResource extends JsonResource
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
                'sharpness_S' => $this->sharpness_S,
                'loudness_N' => $this->loudness_N,
                'roughtness_R' => $this->roughness_R,
                'fluctuation_strength_F' => $this->fluctuation_strength_F,
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
                'pleasant' => $this->transformValues($this->pleasant),
                'chaotic' => $this->transformValues($this->chaotic),
                'vibrant' => $this->transformValues($this->vibrant),
                'uneventful' => $this->transformValues($this->uneventful),
                'calm' => $this->transformValues($this->calm),
                'annoying' => $this->transformValues($this->annoying),
                'eventful' => $this->transformValues($this->eventful),
                'monotonous' => $this->transformValues($this->monotonous),
                'overall' => $this->overall,
                'user_id' => $this->user_id,
                'created_at' => $this->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
                'path' => $this->path,
                'user_local_time' => $this->user_local_time,
            ],
            'relationships' => [
                'user' => UserResource::make($this->user),
                'types' => TypeResource::collection($this->types),
                'segments' => SegmentResource::collection($this->segments),
            ],
        ];
    }

    public function transformValues($value) {
        return match ($value) {
            '5' => 5,
            '4' => 4,
            '3' => 3,
            '2' => 2,
            '1' => 1,
            default => 'N/A',
        };
    }
}

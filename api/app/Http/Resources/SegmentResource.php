<?php

// create segment resource
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class SegmentResource extends JsonResource
{
    public function toArray(Request $request)
    {

        if (is_null($this->resource)) {
            return [];
        }

        return [
            // 'id' => $this->id,
            'position' => $this->position,
            'start_latitude' => $this->start_latitude,
            'start_longitude' => $this->start_longitude,
            'end_latitude' => $this->end_latitude,
            'end_longitude' => $this->end_longitude,
            'L90' => $this->L90,
            'L10' => $this->L10,
            'LAmax' => $this->LAmax,
            'LAmin' => $this->LAmin,
            'LAeq' => $this->LAeq,
            "LAeqT"  => $this->LAeqT,
            "freq_3" => $this->freq_3,
            "spec_3" => $this->spec_3,
            "spec_3_dB" => $this->spec_3_dB,
            'fluctuation' => $this->fluctuation,
            'sharpness' => $this->sharpness,
            'loudness' => $this->loudness,
            'roughness' => $this->roughness,
            'spec_3_dBC' => $this->spec_3_dBC
        ];
    }
}

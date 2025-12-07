<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'rarity' => $this->rarity,
            'tier' => $this->tier,
            'evolution_level' => $this->evolution_level,
            'source' => $this->source,
            'stats' => [
                'hp' => $this->hp,
                'attack' => $this->attack,
                'defense' => $this->defense,
                'speed' => $this->speed,
            ],
            'passive_ability' => $this->passive_ability,
            'sector' => [
                'id' => $this->sector->id,
                'name' => $this->sector->name,
                'slug' => $this->sector->slug,
                'color' => $this->sector->color,
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

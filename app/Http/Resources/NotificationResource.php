<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'user_id' => $this->user_id,
            'rental_id' => $this->rental_id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'is_read' => (bool) $this->is_read,
            'rental' => $this->whenLoaded('rental', function () {
                return [
                    'id' => $this->rental->id,
                    'user_id' => $this->rental->user_id,
                    'camera_id' => $this->rental->camera_id,
                    'start_date' => $this->rental->start_date,
                    'due_date' => $this->rental->due_date,
                    'returned_at' => $this->rental->returned_at,
                    'status' => $this->rental->status,
                    'total_price' => $this->rental->total_price,
                ];
            }),
            'camera' => $this->whenLoaded('rental', function () {
                return [
                    'id' => $this->rental->camera->id,
                    'name' => $this->rental->camera->name,
                    'brand' => $this->rental->camera->brand,
                    'model' => $this->rental->camera->model,
                ] ?? null;
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

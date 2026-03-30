<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'message' => $this->message,
            'recipients' => $this->recipients,
            'scheduled_at' => $this->scheduled_at,
            'sent_at' => $this->sent_at,
            'status' => $this->status,
            'creator' => $this->whenLoaded('creator', fn() => [
                'id' => $this->creator->id,
                'full_name' => $this->creator->fullName(),
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

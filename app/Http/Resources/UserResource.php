<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'role' => $this->role,
            'sex' => $this->sex,
            'contact' => $this->contact,
            'address' => $this->address,
            'status' => $this->status,
            'full_name' => $this->fullName(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

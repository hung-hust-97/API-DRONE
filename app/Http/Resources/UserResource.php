<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public    $successStatus = 200;
    public function toArray($request)
    {
        
        return (
            [
                'id' => $this->id,
                'user' => $this->user,
                'name' => $this->name,
                'avatar' => $this->avatar,
                'address' => $this->address,
                'phone' => $this->phone,
                'email' => $this->email,
                // 'created_at' => $this->created_at,
                // 'updated_at' => $this->updated_at,
            ]
        );
    }
}

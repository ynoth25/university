<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentRequestResource extends JsonResource
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
            'request_id' => $this->request_id,
            'learning_reference_number' => $this->learning_reference_number,
            'name_of_student' => $this->name_of_student,
            'last_schoolyear_attended' => $this->last_schoolyear_attended,
            'gender' => $this->gender,
            'grade' => $this->grade,
            'section' => $this->section,
            'major' => $this->major,
            'adviser' => $this->adviser,
            'contact_number' => $this->contact_number,
            'person_requesting_name' => $this->person_requesting_name,
            'request_for' => $this->request_for,
            'signature_url' => $this->signature_url,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'processed_at' => $this->processed_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}

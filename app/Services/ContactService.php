<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactCustomField;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContactService
{
    public function createContact(array $data): Contact
    {
        return DB::transaction(function () use ($data) {
            // Handle file uploads if present
            if (isset($data['profile_image']) && $data['profile_image'] instanceof \Illuminate\Http\UploadedFile) {
                $data['profile_image'] = $data['profile_image']->store('profile_images', 'public');
            }

            if (isset($data['additional_file']) && $data['additional_file'] instanceof \Illuminate\Http\UploadedFile) {
                $data['additional_file'] = $data['additional_file']->store('additional_files', 'public');
            }

            $contactData = collect($data)->only([
                'name',
                'email',
                'phone',
                'gender',
                'profile_image',
                'additional_file'
            ])->toArray();

            $contact = Contact::create($contactData);

            if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
                foreach ($data['custom_fields'] as $field) {
                    if (!empty($field['key']) && !empty($field['value'])) {
                        $contact->fields()->create([
                            'field_name' => $field['key'],
                            'field_value' => $field['value'],
                            'is_searchable' => true, // Default to searchable as per requirements implies usefulness
                        ]);
                    }
                }
            }

            return $contact;
        });
    }
}

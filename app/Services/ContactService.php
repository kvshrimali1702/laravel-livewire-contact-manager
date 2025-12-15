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

    public function updateContact(Contact $contact, array $data): Contact
    {
        return DB::transaction(function () use ($contact, $data) {
            // Handle Profile Image replacement
            if (isset($data['profile_image']) && $data['profile_image'] instanceof \Illuminate\Http\UploadedFile) {
                if ($contact->profile_image) {
                    Storage::disk('public')->delete($contact->profile_image);
                }
                $data['profile_image'] = $data['profile_image']->store('profile_images', 'public');
            } else {
                unset($data['profile_image']);
            }

            // Handle Additional File replacement
            if (isset($data['additional_file']) && $data['additional_file'] instanceof \Illuminate\Http\UploadedFile) {
                if ($contact->additional_file) {
                    Storage::disk('public')->delete($contact->additional_file);
                }
                $data['additional_file'] = $data['additional_file']->store('additional_files', 'public');
            } else {
                unset($data['additional_file']);
            }

            $contact->update(collect($data)->only([
                'name',
                'email',
                'phone',
                'gender',
                'profile_image',
                'additional_file'
            ])->toArray());

            // Sync Custom Fields
            if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
                $processedIds = [];
                foreach ($data['custom_fields'] as $field) {
                    if (!empty($field['key']) && !empty($field['value'])) {
                        if (isset($field['id']) && $field['id']) {
                            // Update existing
                            $customField = $contact->fields()->find($field['id']);
                            if ($customField) {
                                $customField->update([
                                    'field_name' => $field['key'],
                                    'field_value' => $field['value'],
                                ]);
                                $processedIds[] = $field['id'];
                            }
                        } else {
                            // Create new
                            $newField = $contact->fields()->create([
                                'field_name' => $field['key'],
                                'field_value' => $field['value'],
                                'is_searchable' => true,
                            ]);
                            $processedIds[] = $newField->id;
                        }
                    }
                }
                // Delete missing
                $contact->fields()->whereNotIn('id', $processedIds)->delete();
            }

            return $contact;
        });
    }

    public function deleteContactFile(Contact $contact, string $type): void
    {
        if ($type === 'profile_image' && $contact->profile_image) {
            Storage::disk('public')->delete($contact->profile_image);
            $contact->update(['profile_image' => null]);
        } elseif ($type === 'additional_file' && $contact->additional_file) {
            Storage::disk('public')->delete($contact->additional_file);
            $contact->update(['additional_file' => null]);
        }
    }
}

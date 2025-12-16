<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Collection;
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
                'additional_file',
            ])->toArray();

            $contact = Contact::create($contactData);

            if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
                foreach ($data['custom_fields'] as $field) {
                    if (! empty($field['key']) && ! empty($field['value'])) {
                        $contact->fields()->create([
                            'field_name' => $field['key'],
                            'field_value' => $field['value'],
                            'is_searchable' => isset($field['is_searchable']) ? (bool) $field['is_searchable'] : false,
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
                'additional_file',
            ])->toArray());

            // Sync Custom Fields
            if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
                $processedIds = [];
                foreach ($data['custom_fields'] as $field) {
                    if (! empty($field['key']) && ! empty($field['value'])) {
                        if (isset($field['id']) && $field['id']) {
                            // Update existing
                            $customField = $contact->fields()->find($field['id']);
                            if ($customField) {
                                $customField->update([
                                    'field_name' => $field['key'],
                                    'field_value' => $field['value'],
                                    'is_searchable' => isset($field['is_searchable']) ? (bool) $field['is_searchable'] : false,
                                ]);
                                $processedIds[] = $field['id'];
                            }
                        } else {
                            // Create new
                            $newField = $contact->fields()->create([
                                'field_name' => $field['key'],
                                'field_value' => $field['value'],
                                'is_searchable' => isset($field['is_searchable']) ? (bool) $field['is_searchable'] : false,
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

    /**
     * Mark a contact as secondary of the provided master contact.
     *
     * Data is intentionally not merged; we only track linkage.
     */
    public function mergeContacts(Contact $master, Contact $secondary): Contact
    {
        return DB::transaction(function () use ($master, $secondary) {
            if ($master->is($secondary)) {
                throw new \InvalidArgumentException('Master and secondary contacts must differ.');
            }

            // Disallow re-merging contacts that are already in a merge relationship.
            $master->loadMissing('secondaryContacts');
            $secondary->loadMissing('secondaryContacts');

            if ($master->master_id !== null || $secondary->master_id !== null || $master->secondaryContacts()->exists() || $secondary->secondaryContacts()->exists()) {
                throw new \InvalidArgumentException('One or both contacts are already merged and cannot be merged again.');
            }

            if ($this->isDescendant($master, $secondary)) {
                throw new \InvalidArgumentException('Cannot make a master a child of its descendant.');
            }

            $secondary->master_id = $master->id;
            $secondary->save();

            return $secondary;
        });
    }

    /**
     * Check if $target exists within $candidate's descendant tree.
     */
    private function isDescendant(Contact $candidate, Contact $target): bool
    {
        $toVisit = new Collection([$candidate]);

        while ($toVisit->isNotEmpty()) {
            $current = $toVisit->shift();
            $current->loadMissing('secondaryContacts');

            if ($current->secondaryContacts->contains('id', $target->id)) {
                return true;
            }

            $toVisit = $toVisit->merge($current->secondaryContacts);
        }

        return false;
    }
}

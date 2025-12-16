<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Collection;

class ContactDisplayService
{
    /**
     * Build merged display payload for modal rendering without persisting any data changes.
     */
    public function buildMergedDisplay(Contact $master, ?Contact $secondary = null): array
    {
        $contacts = $this->flattenHierarchy($master);

        if ($secondary && ! $contacts->contains(fn ($item) => $item->id === $secondary->id)) {
            $contacts->push($secondary);
        }

        $names = $contacts->map(function (Contact $contact) {
            return $contact->name;
        })->unique()->values();

        $emails = $this->buildUniqueEmailsCollection($contacts);

        $phones = $this->buildUniquePhonesCollection($contacts);

        $customFields = $this->buildUniqueCustomFields($contacts);

        return [
            'names' => $names->toArray(),
            'emails' => $emails->toArray(),
            'phones' => $phones->toArray(),
            'custom_fields' => array_values($customFields),
            'secondary_tree' => $this->buildSecondaryTree($master),
        ];
    }

    private function flattenHierarchy(Contact $contact): Collection
    {
        $collection = new Collection([$contact]);

        $contact->loadMissing('secondaryContacts');

        foreach ($contact->secondaryContacts as $child) {
            $collection = $collection->merge($this->flattenHierarchy($child));
        }

        return $collection;
    }

    private function buildSecondaryTree(Contact $contact): array
    {
        $contact->loadMissing('fields', 'secondaryContacts');

        return $contact->secondaryContacts->map(function (Contact $child) {
            return [
                'id' => $child->id,
                'name' => $child->name,
                'email' => $child->email,
                'phone' => $child->phone,
                'gender' => $child->gender instanceof \App\Enums\GenderOptions ? $child->gender->label() : ($child->gender ?? ''),
                'fields' => $child->fields->map(function ($field) {
                    return [
                        'name' => $field->field_name,
                        'value' => $field->field_value,
                        'is_searchable' => (bool) $field->is_searchable,
                    ];
                })->toArray(),
                'children' => $this->buildSecondaryTree($child),
            ];
        })->toArray();
    }

    /**
     * Build a unique collection of email records across a hierarchy of contacts.
     *
     * Two email entries are considered the same when their trimmed, lower‑cased values match.
     */
    private function buildUniqueEmailsCollection(Collection $contacts): Collection
    {
        $seen = [];

        return $contacts->reduce(function (Collection $carry, Contact $contact) use (&$seen) {
            $raw = (string) $contact->email;

            if ($raw === '') {
                return $carry;
            }

            $normalized = mb_strtolower(trim($raw));

            if (isset($seen[$normalized])) {
                return $carry;
            }

            $seen[$normalized] = true;

            $carry->push([
                'value' => $raw,
                'source' => $contact->name,
                'contact_id' => $contact->id,
            ]);

            return $carry;
        }, new Collection)->values();
    }

    /**
     * Build a unique collection of phone records across a hierarchy of contacts.
     *
     * Two phone entries are considered the same when their normalized digits (plus optional leading +)
     * match, so minor formatting differences (spaces, dashes, brackets) do not create duplicates.
     */
    private function buildUniquePhonesCollection(Collection $contacts): Collection
    {
        $seen = [];

        return $contacts->reduce(function (Collection $carry, Contact $contact) use (&$seen) {
            $raw = (string) $contact->phone;

            if ($raw === '') {
                return $carry;
            }

            $normalized = preg_replace('/[^\d+]/', '', $raw ?? '');

            if ($normalized === '') {
                return $carry;
            }

            if (isset($seen[$normalized])) {
                return $carry;
            }

            $seen[$normalized] = true;

            $carry->push([
                'value' => $raw,
                'source' => $contact->name,
                'contact_id' => $contact->id,
            ]);

            return $carry;
        }, new Collection)->values();
    }

    /**
     * Build a unique array of custom fields keyed by field name with de‑duplicated values.
     *
     * - Field names are grouped by their trimmed value.
     * - Values are considered the same when their trimmed, lower‑cased strings match.
     */
    private function buildUniqueCustomFields(Collection $contacts): array
    {
        $customFields = [];
        $seenPerField = [];

        $contacts->each(function (Contact $contact) use (&$customFields, &$seenPerField) {
            $contact->loadMissing('fields');

            foreach ($contact->fields as $field) {
                $fieldName = trim((string) $field->field_name);

                if ($fieldName === '') {
                    continue;
                }

                if (! isset($customFields[$fieldName])) {
                    $customFields[$fieldName] = [
                        'field_name' => $fieldName,
                        'values' => [],
                    ];

                    $seenPerField[$fieldName] = [];
                }

                $rawValue = (string) $field->field_value;
                $normalizedValue = mb_strtolower(trim($rawValue));

                if ($normalizedValue === '') {
                    continue;
                }

                if (isset($seenPerField[$fieldName][$normalizedValue])) {
                    continue;
                }

                $seenPerField[$fieldName][$normalizedValue] = true;

                $customFields[$fieldName]['values'][] = [
                    'value' => $rawValue,
                    'source' => $contact->name,
                    'contact_id' => $contact->id,
                    'is_searchable' => (bool) $field->is_searchable,
                ];
            }
        });

        return array_values($customFields);
    }
}

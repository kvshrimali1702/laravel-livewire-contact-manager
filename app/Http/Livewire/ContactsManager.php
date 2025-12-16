<?php

namespace App\Http\Livewire;

use App\Models\Contact;
use App\Services\ContactService;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class ContactsManager extends Component
{
    use WireUiActions, WithPagination;

    protected $paginationTheme = 'tailwind';

    public int $perPage = 10;

    public ?string $search = null;

    public array $selectedGenders = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'selectedGenders' => ['except' => []],
    ];

    protected $listeners = [
        'contact-created' => '$refresh',
        'contact-updated' => '$refresh',
        'open-merge-modal' => 'openMergeModal',
    ];

    public bool $viewModalOpen = false;

    public ?Contact $viewingContact = null;

    public array $viewingMergedDisplay = [];

    public bool $mergeModalOpen = false;

    public ?int $mergeMasterId = null;

    public ?int $mergeSecondaryId = null;

    public array $mergePreview = [];

    /**
     * Contacts that are already part of a merge (either as master or secondary).
     *
     * @var array<int,int>
     */
    public array $mergeLockedIds = [];

    public array $mergedListDisplays = [];

    public function openViewModal(int $id): void
    {
        $contact = Contact::find($id);

        if (! $contact) {
            return;
        }

        $this->loadDescendants($contact);

        $this->viewingContact = $contact;
        $this->viewingMergedDisplay = $this->buildMergedDisplay($contact);
        $this->viewModalOpen = true;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedGenders()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Contact::with('fields')
            ->whereNull('master_id')
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhereHas('fields', function ($q2) use ($term) {
                            $q2->where(function ($q3) use ($term) {
                                $q3
                                    ->where('field_value', 'like', $term)
                                    ->orWhere('field_name', 'like', $term);
                            })
                                ->where('is_searchable', true);
                        });
                });
            })
            ->when(! empty($this->selectedGenders), function ($query) {
                $query->whereIn('gender', $this->selectedGenders);
            })
            ->orderBy('created_at', 'desc');

        $contacts = $query->paginate($this->perPage);

        // Pre-compute merged displays for listing.
        $this->mergedListDisplays = [];
        foreach ($contacts as $contact) {
            $this->loadDescendants($contact);
            $this->mergedListDisplays[$contact->id] = $this->buildMergedDisplay($contact);
        }

        // Determine which contacts are already merged (either as master or secondary)
        // so they cannot participate in additional merges.
        $this->mergeLockedIds = Contact::query()
            ->whereNotNull('master_id')
            ->orWhereHas('secondaryContacts')
            ->pluck('id')
            ->all();

        return view('livewire.contacts-manager', [
            'contacts' => $contacts,
            'allContacts' => Contact::orderBy('name')->get(['id', 'name', 'email', 'phone', 'master_id']),
        ]);
    }

    /**
     * Confirm delete contact.
     */
    public function confirmDelete(int $id): void
    {
        $this->dialog()->confirm([
            'title' => 'Are you sure?',
            'description' => 'Do you really want to delete this contact? This action cannot be undone.',
            'acceptLabel' => 'Yes, delete it',
            'method' => 'deleteContact',
            'params' => $id,
        ]);
    }

    /**
     * Delete a contact by id.
     */
    public function deleteContact(int $id): void
    {
        $contact = Contact::find($id);

        if (! $contact) {
            // contact not found; simply return silently
            return;
        }

        $contact->delete();
        // Reset to first page to avoid empty-page state after deletion.
        $this->resetPage();
        // Dispatch a global app alert (Livewire v3 style)
        $this->dispatch('app:alert', title: 'Deleted', description: 'Contact deleted successfully.', color: 'positive');
    }

    public function openMergeModal(?int $masterId = null): void
    {
        $this->resetMergeState();
        $this->mergeMasterId = $masterId;
        $this->mergeModalOpen = true;
    }

    public function updatedMergeMasterId(): void
    {
        $this->mergePreview = [];
    }

    public function updatedMergeSecondaryId(): void
    {
        $this->mergePreview = [];
    }

    public function prepareMergePreview(): void
    {
        try {
            $this->validateMergeSelection();
        } catch (\InvalidArgumentException $exception) {
            $this->dialog()->error('Invalid merge', $exception->getMessage());

            return;
        }

        $master = Contact::with('fields')->find($this->mergeMasterId);
        $secondary = Contact::with('fields')->find($this->mergeSecondaryId);

        if (! $master || ! $secondary) {
            return;
        }

        $this->loadDescendants($master);
        $this->loadDescendants($secondary);

        $combined = $this->buildMergedDisplay($master, $secondary);

        $this->mergePreview = [
            'master' => $master->only(['id', 'name', 'email', 'phone']),
            'secondary' => $secondary->only(['id', 'name', 'email', 'phone']),
            'merged' => $combined,
        ];
    }

    public function confirmMerge(ContactService $contactService): void
    {
        try {
            $this->validateMergeSelection();
        } catch (\InvalidArgumentException $exception) {
            $this->dialog()->error('Invalid merge', $exception->getMessage());

            return;
        }

        $master = Contact::find($this->mergeMasterId);
        $secondary = Contact::find($this->mergeSecondaryId);

        if (! $master || ! $secondary) {
            return;
        }

        try {
            $contactService->mergeContacts($master, $secondary);
        } catch (\InvalidArgumentException $exception) {
            $this->dialog()->error('Invalid merge', $exception->getMessage());

            return;
        } catch (\Throwable $exception) {
            $this->dialog()->error('Merge failed', 'An unexpected error occurred.');

            return;
        }

        $this->mergeModalOpen = false;
        $this->resetMergeState();
        $this->dispatch('app:alert', title: 'Merged', description: 'Contact linked as secondary.', color: 'positive');
        $this->resetPage();
    }

    /**
     * Confirm unmerge contact.
     */
    public function confirmUnmerge(int $id): void
    {
        $this->dialog()->confirm([
            'title' => 'Unmerge Contact?',
            'description' => 'Are you sure you want to unmerge this contact? All secondary contacts will be detached and become independent.',
            'acceptLabel' => 'Yes, unmerge',
            'method' => 'unmergeContact',
            'params' => $id,
        ]);
    }

    /**
     * Unmerge a contact by id (detach all secondary contacts).
     */
    public function unmergeContact(int $id): void
    {
        $contact = Contact::find($id);

        if (! $contact) {
            return;
        }

        // Set master_id to null for all immediate secondary contacts
        $contact->secondaryContacts()->update(['master_id' => null]);

        $this->dispatch('app:alert', title: 'Unmerged', description: 'Contacts have been unmerged successfully.', color: 'positive');
        $this->resetPage(); // Refresh the list
    }

    private function resetMergeState(): void
    {
        $this->reset(['mergeMasterId', 'mergeSecondaryId', 'mergePreview']);
    }

    private function validateMergeSelection(): void
    {
        $this->validate([
            'mergeMasterId' => 'required|different:mergeSecondaryId|exists:contacts,id',
            'mergeSecondaryId' => 'required|different:mergeMasterId|exists:contacts,id',
        ]);

        $master = Contact::with('secondaryContacts')->find($this->mergeMasterId);
        $secondary = Contact::with('secondaryContacts')->find($this->mergeSecondaryId);

        if (! $master || ! $secondary) {
            return;
        }

        // Prevent contacts that are already involved in a merge (as master or secondary)
        // from being merged again.
        if ($master->master_id !== null || $secondary->master_id !== null || $master->secondaryContacts()->exists() || $secondary->secondaryContacts()->exists()) {
            throw new \InvalidArgumentException('One or both contacts are already merged and cannot be merged again.');
        }

        if ($secondary->master_id === $master->id) {
            return;
        }

        if ($this->isDescendant($secondary, $master)) {
            throw new \InvalidArgumentException('Cannot merge a master into its descendant.');
        }
    }

    private function loadDescendants(Contact $contact): void
    {
        $contact->loadMissing('fields', 'secondaryContacts');
        $contact->secondaryContacts->each(function (Contact $child) {
            $this->loadDescendants($child);
        });
    }

    private function isDescendant(Contact $root, Contact $candidate): bool
    {
        $queue = new Collection([$root]);

        while ($queue->isNotEmpty()) {
            $current = $queue->shift();
            $current->loadMissing('secondaryContacts');

            if ($current->secondaryContacts->contains('id', $candidate->id)) {
                return true;
            }

            $queue = $queue->merge($current->secondaryContacts);
        }

        return false;
    }

    /**
     * Build merged display payload for modal rendering without persisting any data changes.
     */
    private function buildMergedDisplay(Contact $master, ?Contact $secondary = null): array
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
                'gender' => $child->gender,
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

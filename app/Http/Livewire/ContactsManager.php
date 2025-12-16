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

        $emails = $contacts->map(function (Contact $contact) {
            return [
                'value' => $contact->email,
                'source' => $contact->name,
                'contact_id' => $contact->id,
            ];
        })->unique('value')->values();

        $phones = $contacts->map(function (Contact $contact) {
            return [
                'value' => $contact->phone,
                'source' => $contact->name,
                'contact_id' => $contact->id,
            ];
        })->unique('value')->values();

        $customFields = [];

        $contacts->each(function (Contact $contact) use (&$customFields) {
            $contact->loadMissing('fields');
            foreach ($contact->fields as $field) {
                $key = $field->field_name;

                if (! isset($customFields[$key])) {
                    $customFields[$key] = [
                        'field_name' => $key,
                        'values' => [],
                    ];
                }

                $exists = collect($customFields[$key]['values'])->contains(fn ($item) => $item['value'] === $field->field_value);

                if (! $exists) {
                    $customFields[$key]['values'][] = [
                        'value' => $field->field_value,
                        'source' => $contact->name,
                        'contact_id' => $contact->id,
                        'is_searchable' => (bool) $field->is_searchable,
                    ];
                }
            }
        });

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
}

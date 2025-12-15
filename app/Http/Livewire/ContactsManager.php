<?php

namespace App\Http\Livewire;

use App\Models\Contact;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class ContactsManager extends Component
{
    use WithPagination, WireUiActions;

    protected $paginationTheme = 'tailwind';

    public int $perPage = 10;
    public ?string $search = null;
    public array $selectedGenders = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'selectedGenders' => ['except' => []],
    ];

    protected $listeners = ['contact-created' => '$refresh'];

    public bool $viewModalOpen = false;
    public ?Contact $viewingContact = null;

    public function openViewModal(int $id): void
    {
        $this->viewingContact = Contact::with('fields')->find($id);
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
            ->when(!empty($this->selectedGenders), function ($query) {
                $query->whereIn('gender', $this->selectedGenders);
            })
            ->orderBy('created_at', 'desc');

        $contacts = $query->paginate($this->perPage);

        return view('livewire.contacts-manager', [
            'contacts' => $contacts,
        ]);
    }

    /**
     * Confirm delete contact.
     */
    public function confirmDelete(int $id): void
    {
        $this->dialog()->confirm([
            'title'       => 'Are you sure?',
            'description' => 'Do you really want to delete this contact? This action cannot be undone.',
            'acceptLabel' => 'Yes, delete it',
            'method'      => 'deleteContact',
            'params'      => $id,
        ]);
    }

    /**
     * Delete a contact by id.
     */
    public function deleteContact(int $id): void
    {
        $contact = Contact::find($id);

        if (!$contact) {
            // contact not found; simply return silently
            return;
        }

        $contact->delete();
        // Reset to first page to avoid empty-page state after deletion.
        $this->resetPage();
        // Dispatch a global app alert (Livewire v3 style)
        $this->dispatch('app:alert', title: 'Deleted', description: 'Contact deleted successfully.', color: 'positive');
    }
}

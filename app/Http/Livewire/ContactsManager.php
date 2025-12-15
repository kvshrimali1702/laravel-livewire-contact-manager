<?php

namespace App\Http\Livewire;

use App\Models\Contact;
use Livewire\Component;
use Livewire\WithPagination;

class ContactsManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public int $perPage = 10;
    public ?string $search = null;

    protected $queryString = ['search'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Contact::with('fields')
            ->when($this->search, fn($q) => $q->where(fn($q2) => $q2->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
            ->orderBy('created_at', 'desc');

        $contacts = $query->paginate($this->perPage);

        return view('livewire.contacts-manager', [
            'contacts' => $contacts,
        ]);
    }
}

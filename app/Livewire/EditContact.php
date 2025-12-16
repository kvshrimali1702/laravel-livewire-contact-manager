<?php

namespace App\Livewire;

use App\Enums\GenderOptions;
use App\Models\Contact;
use App\Services\ContactService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\WireUiActions;

class EditContact extends Component
{
    use WireUiActions, WithFileUploads;

    public bool $modalOpen = false;

    public $contactId;

    /**
     * All contacts that visually belong to the same merged group.
     * This always includes the master contact and any secondaries.
     *
     * @var array<int, array{id:int,label:string,is_master:bool}>
     */
    public array $groupContacts = [];

    /**
     * Currently selected contact within the merged group being edited.
     *
     * @var int|null
     */
    public $activeContactId = null;

    public $name;

    public $email;

    public $phone;

    public $gender;

    // Existing file paths
    public $existingProfileImage;

    public $existingAdditionalFile;

    // New file uploads
    public $newProfileImage;

    public $newAdditionalFile;

    // Array to hold custom fields: [['id' => ?, 'key' => '', 'value' => '']]
    public array $customFields = [];

    protected $listeners = ['edit-contact' => 'loadContact'];

    protected function rules()
    {
        return [
            'activeContactId' => 'required|integer|exists:contacts,id',
            'name' => 'required|min:3|max:100',
            'email' => 'required|email|max:100',
            'phone' => 'required|digits_between:8,14',
            'gender' => ['required', Rule::enum(GenderOptions::class)],
            'newProfileImage' => 'nullable|image|max:1024',
            'newAdditionalFile' => 'nullable|file|max:2048',
            'customFields.*.key' => 'required|string',
            'customFields.*.value' => 'required|string',
            'customFields.*.is_searchable' => 'boolean',
        ];
    }

    protected $messages = [
        'customFields.*.key.required' => 'Field name is required.',
        'customFields.*.value.required' => 'Field value is required.',
    ];

    public function loadContact(int $id)
    {
        $contact = Contact::with(['fields', 'master', 'secondaryContacts'])->find($id);

        if (! $contact) {
            $this->notification()->error(
                title: 'Error',
                description: 'Contact not found.'
            );

            return;
        }

        $this->resetValidation();
        $this->reset(['newProfileImage', 'newAdditionalFile', 'groupContacts', 'activeContactId']);

        // Determine the root/master contact for this merged group.
        $root = $contact->master ?? $contact;
        $root->loadMissing('secondaryContacts');

        // Build a lightweight list of all contacts that are visually grouped together.
        $this->groupContacts = $this->buildGroupContacts($root);

        // Always default the selector to the master contact.
        $this->activeContactId = $root->id;

        // Load the master contact's data into the form by default.
        $this->applyContact($root);

        $this->modalOpen = true;
    }

    /**
     * When the user switches which contact inside the merged group they're editing,
     * reload that specific contact's data into the form.
     */
    public function updatedActiveContactId(): void
    {
        // Prevent the selector from ending up empty; always fall back to the master.
        if (! $this->activeContactId && $this->groupContacts !== []) {
            $this->activeContactId = $this->groupContacts[0]['id'] ?? null;
        }

        if (! $this->activeContactId) {
            return;
        }

        $contact = Contact::with('fields')->find((int) $this->activeContactId);

        if (! $contact) {
            return;
        }

        $this->applyContact($contact);
    }

    /**
     * Populate the form fields from the given contact.
     */
    private function applyContact(Contact $contact): void
    {
        $this->contactId = $contact->id;
        $this->name = $contact->name;
        $this->email = $contact->email;
        $this->phone = $contact->phone;
        $this->gender = (string) $contact->gender->value;
        $this->existingProfileImage = $contact->profile_image;
        $this->existingAdditionalFile = $contact->additional_file;

        $this->customFields = $contact->fields->map(function ($field) {
            return [
                'id' => $field->id,
                'key' => $field->field_name,
                'value' => $field->field_value,
                'is_searchable' => (bool) $field->is_searchable,
            ];
        })->toArray();
    }

    /**
     * Build the list of contacts that belong to the merged group for UI selection.
     *
     * @return array<int, array{id:int,label:string,is_master:bool}>
     */
    private function buildGroupContacts(Contact $root): array
    {
        $contacts = [];

        $contacts[] = [
            'id' => $root->id,
            'label' => "{$root->name} (master)",
            'is_master' => true,
        ];

        $root->loadMissing('secondaryContacts');

        foreach ($root->secondaryContacts as $secondary) {
            $contacts[] = [
                'id' => $secondary->id,
                'label' => "{$secondary->name} (secondary)",
                'is_master' => false,
            ];
        }

        return $contacts;
    }

    public function addCustomField()
    {
        $this->customFields[] = ['id' => null, 'key' => '', 'value' => '', 'is_searchable' => false];
    }

    public function removeCustomField($index)
    {
        unset($this->customFields[$index]);
        $this->customFields = array_values($this->customFields);
    }

    public function deleteFileConfirmation(string $type)
    {
        $this->dialog()->confirm([
            'title' => 'Are you sure?',
            'description' => 'This will permanently delete the file.',
            'acceptLabel' => 'Yes, delete it',
            'method' => 'deleteFile',
            'params' => $type,
        ]);
    }

    public function deleteFile(string $type, ContactService $contactService)
    {
        $contact = Contact::find($this->contactId);

        if ($contact) {
            $contactService->deleteContactFile($contact, $type);

            // Refresh local state
            if ($type === 'profile_image') {
                $this->existingProfileImage = null;
            } elseif ($type === 'additional_file') {
                $this->existingAdditionalFile = null;
            }

            $this->notification()->success(
                title: 'Deleted',
                description: 'File deleted successfully.'
            );
        }
    }

    public function save(ContactService $contactService)
    {
        $this->validate();

        $contact = Contact::find($this->contactId);

        if (! $contact) {
            return;
        }

        try {
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'gender' => $this->gender,
                'profile_image' => $this->newProfileImage,
                'additional_file' => $this->newAdditionalFile,
                'custom_fields' => $this->customFields,
            ];

            $contactService->updateContact($contact, $data);

            $this->modalOpen = false;
            $this->notification()->success(
                title: 'Contact Updated',
                description: 'The contact has been successfully updated.'
            );

            $this->dispatch('contact-updated'); // Listen in Manager if needed, or just $refresh via simple refresh
            $this->dispatch('contact-created'); // Reuse existing listener in Manager for simplicity? Or add new one.

        } catch (\Exception $e) {
            $this->notification()->error(
                title: 'Error Updating Contact',
                description: 'An error occurred: '.$e->getMessage()
            );
        }
    }

    public function render()
    {
        // Ensure selector is never empty when we already know the group contacts.
        if ($this->groupContacts !== [] && ! $this->activeContactId) {
            $this->activeContactId = $this->groupContacts[0]['id'] ?? null;
        }

        return view('livewire.edit-contact', [
            'genders' => GenderOptions::cases(),
        ]);
    }
}

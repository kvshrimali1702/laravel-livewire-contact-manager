<?php

namespace App\Livewire;

use App\Enums\GenderOptions;
use App\Services\ContactService;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\WireUiActions;

class CreateContact extends Component
{
    use WithFileUploads, WireUiActions;

    public bool $modalOpen = false;

    public $name;
    public $email;
    public $phone;
    public $gender;
    public $profile_image;
    public $additional_file;

    // Array to hold custom fields: [['key' => '', 'value' => '']]
    public array $customFields = [];

    protected function rules()
    {
        return [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email|max:100',
            'phone' => 'required|digits_between:8,14',
            'gender' => ['required', \Illuminate\Validation\Rule::enum(GenderOptions::class)],
            'profile_image' => 'nullable|image|max:1024', // 1MB Max
            'additional_file' => 'nullable|file|max:2048', // 2MB Max
            'customFields.*.key' => 'required|string',
            'customFields.*.value' => 'required|string',
            'customFields.*.is_searchable' => 'boolean',
        ];
    }

    protected $messages = [
        'customFields.*.key.required' => 'Field name is required.',
        'customFields.*.value.required' => 'Field value is required.',
    ];

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['name', 'email', 'phone', 'gender', 'profile_image', 'additional_file', 'customFields']);
        $this->modalOpen = true;
    }

    public function addCustomField()
    {
        $this->customFields[] = ['key' => '', 'value' => '', 'is_searchable' => false];
    }

    public function removeCustomField($index)
    {
        unset($this->customFields[$index]);
        $this->customFields = array_values($this->customFields);
    }

    public function save(ContactService $contactService)
    {
        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'gender' => $this->gender,
                'profile_image' => $this->profile_image,
                'additional_file' => $this->additional_file,
                'custom_fields' => $this->customFields,
            ];

            $contactService->createContact($data);

            $this->modalOpen = false;
            $this->notification()->success(
                title: 'Contact Saved',
                description: 'The contact has been successfully created.'
            );
            
            // Dispatch event for ContactsManager to refresh
            $this->dispatch('contact-created');

        } catch (\Exception $e) {
            $this->notification()->error(
                title: 'Error Saving Contact',
                description: 'An error occurred while saving the contact: ' . $e->getMessage()
            );
        }
    }

    public function render()
    {
        return view('livewire.create-contact', [
            'genders' => GenderOptions::cases(),
        ]);
    }
}

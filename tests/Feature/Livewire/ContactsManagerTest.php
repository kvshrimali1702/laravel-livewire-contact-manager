<?php

namespace Tests\Feature\Livewire;

use App\Http\Livewire\ContactsManager;
use App\Models\Contact;
use App\Models\ContactCustomField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactsManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_search_contacts_by_phone()
    {
        Contact::factory()->create([
            'name' => 'John Doe',
            'phone' => '1234567890',
        ]);

        Contact::factory()->create([
            'name' => 'Jane Doe',
            'phone' => '0987654321',
        ]);

        Livewire::test(ContactsManager::class)
            ->set('search', '1234567890')
            ->assertSee('John Doe')
            ->assertDontSee('Jane Doe');
    }

    public function test_can_search_contacts_by_searchable_custom_field()
    {
        $contact1 = Contact::factory()->create(['name' => 'Alice']);
        ContactCustomField::create([
            'contact_id' => $contact1->id,
            'field_name' => 'Company',
            'field_value' => 'Acme Corp',
            'is_searchable' => true,
        ]);

        $contact2 = Contact::factory()->create(['name' => 'Bob']);
        ContactCustomField::create([
            'contact_id' => $contact2->id,
            'field_name' => 'Company',
            'field_value' => 'Globex',
            'is_searchable' => true,
        ]);

        Livewire::test(ContactsManager::class)
            ->set('search', 'Acme')
            ->assertSee('Alice')
            ->assertDontSee('Bob');
    }

    public function test_cannot_search_contacts_by_non_searchable_custom_field()
    {
        $contact1 = Contact::factory()->create(['name' => 'Alice']);
        ContactCustomField::create([
            'contact_id' => $contact1->id,
            'field_name' => 'Secret',
            'field_value' => 'HiddenValue',
            'is_searchable' => false,
        ]);

        Livewire::test(ContactsManager::class)
            ->set('search', 'HiddenValue')
            ->assertDontSee('Alice');
    }
}

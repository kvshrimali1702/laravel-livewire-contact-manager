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

    /** @test */
    public function test_can_view_contact_details()
    {
        $contact = Contact::factory()->create();

        Livewire::test(ContactsManager::class)
            ->call('openViewModal', $contact->id)
            ->assertSet('viewModalOpen', true)
            ->assertSet('viewingContact.id', $contact->id)
            ->assertSet('viewingContact.name', $contact->name);
    }

    public function test_can_merge_contact_and_hide_secondary_from_list()
    {
        $master = Contact::factory()->create(['name' => 'Master Contact']);
        $secondary = Contact::factory()->create(['name' => 'Secondary Contact']);

        $component = Livewire::test(ContactsManager::class)
            ->call('openMergeModal', $master->id)
            ->set('mergeSecondaryId', $secondary->id)
            ->call('prepareMergePreview')
            ->call('confirmMerge');

        $this->assertDatabaseHas('contacts', [
            'id' => $secondary->id,
            'master_id' => $master->id,
        ]);

        $component->assertSee('Master Contact')
            ->assertSee('Secondary Contact');
    }

    public function test_view_modal_shows_combined_data_after_merge()
    {
        $master = Contact::factory()->create([
            'name' => 'Root',
            'email' => 'root@example.com',
            'phone' => '11111111',
        ]);

        $secondary = Contact::factory()->create([
            'name' => 'Child',
            'email' => 'child@example.com',
            'phone' => '22222222',
            'master_id' => $master->id,
        ]);

        ContactCustomField::create([
            'contact_id' => $master->id,
            'field_name' => 'Company',
            'field_value' => 'Acme',
            'is_searchable' => true,
        ]);

        ContactCustomField::create([
            'contact_id' => $secondary->id,
            'field_name' => 'Title',
            'field_value' => 'Engineer',
            'is_searchable' => false,
        ]);

        $component = Livewire::test(ContactsManager::class)
            ->call('openViewModal', $master->id);

        $merged = $component->get('viewingMergedDisplay');

        $emails = collect($merged['emails'])->pluck('value');
        $phones = collect($merged['phones'])->pluck('value');
        $customFields = collect($merged['custom_fields'])->pluck('field_name');

        $this->assertTrue($emails->contains('root@example.com'));
        $this->assertTrue($emails->contains('child@example.com'));
        $this->assertTrue($phones->contains('11111111'));
        $this->assertTrue($phones->contains('22222222'));
        $this->assertTrue($customFields->contains('Company'));
        $this->assertTrue($customFields->contains('Title'));
    }
}

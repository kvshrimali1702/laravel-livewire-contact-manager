<div class="space-y-4">
    @php
        use App\Enums\GenderOptions;
    @endphp
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 w-full max-w-md">
            <x-input icon="magnifying-glass" placeholder="Search in name, email, phone, custom fields" wire:model.live.debounce.400ms="search" class="w-full" />
        </div>

        
        <div class="flex items-center gap-4">
            @foreach(GenderOptions::cases() as $gender)
                <x-checkbox 
                    id="gender-{{ $gender->value }}"
                    label="{{ $gender->label() }}" 
                    value="{{ $gender->value }}"
                    wire:model.live="selectedGenders" 
                />
            @endforeach
        </div>

        <div class="flex items-center gap-2">
            <x-native-select
                label="Per page"
                wire:model.live="perPage"
                :options="['5' => 5, '10' => 10, '25' => 25, '50' => 50]"
            />
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
            <thead class="bg-neutral-50 dark:bg-neutral-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-300">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-300">Email
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-300">Phone
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-300">Gender
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-300">Custom
                        Fields</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-300">Added
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 dark:text-neutral-300">Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 bg-white dark:divide-neutral-800 dark:bg-neutral-950">
                @forelse($contacts as $contact)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-900 dark:text-neutral-100">
                                    {{ $contact->name }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ $contact->email }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ $contact->phone }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ $contact->gender instanceof GenderOptions ? $contact->gender->label() : '' }}
                                </td>

                                <td class="whitespace-normal px-4 py-3 text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ $contact->fields->map(function ($f) {
                    return $f->field_name . ': ' . $f->field_value; })->implode(', ') }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $contact->created_at->diffForHumans() }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-sm text-right">
                                    <x-mini-button rounded info icon="eye"
                                        wire:click="openViewModal({{ $contact->id }})"
                                    />
                                    <x-mini-button rounded primary icon="pencil"
                                        wire:click="$dispatch('edit-contact', { id: {{ $contact->id }} })"
                                    />
                                    <x-mini-button rounded negative icon="trash"
                                        wire:click="confirmDelete({{ $contact->id }})"
                                    />
                                </td>
                            </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-neutral-500 dark:text-neutral-400">No
                            contacts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between">
        <div class="text-sm text-neutral-600 dark:text-neutral-300">
            Showing <strong>{{ $contacts->firstItem() ?: 0 }}</strong> to
            <strong>{{ $contacts->lastItem() ?: 0 }}</strong> of <strong>{{ $contacts->total() }}</strong>
        </div>

        <div>
    <livewire:edit-contact />
            {{ $contacts->links() }}
        </div>
    </div>

    {{-- View Contact Modal --}}
    <x-modal-card title="Contact Details" wire:model="viewModalOpen">
        @if($viewingContact)
            <div class="space-y-4">
                {{-- Profile Image --}}
                @if($viewingContact->profile_image)
                    <div class="flex justify-center">
                        <img src="{{ Storage::url($viewingContact->profile_image) }}" alt="Profile Image" class="h-32 w-32 rounded-full object-cover border border-gray-200">
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Standard Fields --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Name</label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $viewingContact->name }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Email</label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $viewingContact->email }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Phone</label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $viewingContact->phone }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Gender</label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ $viewingContact->gender instanceof GenderOptions ? $viewingContact->gender->label() : '' }}
                        </div>
                    </div>

                    {{-- Additional File --}}
                    @if($viewingContact->additional_file)
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Additional File</label>
                            <div class="mt-1 text-sm">
                                <a href="{{ Storage::url($viewingContact->additional_file) }}" target="_blank" class="text-blue-600 hover:text-blue-500 underline">
                                    View File
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Custom Fields --}}
                @if($viewingContact->fields->isNotEmpty())
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-2">Custom Fields</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($viewingContact->fields as $field)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $field->field_name }}</label>
                                        <div class="mt-1 text-sm text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                            {{ $field->field_value }}
                                            @if($field->is_searchable)
                                                <x-badge sm outline positive label="Searchable" />
                                            @endif
                                        </div>
                                    </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    
        <x-slot name="footer">
            <div class="flex justify-end gap-x-4">
                <x-button flat label="Close" x-on:click="close" />
            </div>
        </x-slot>
    </x-modal-card>
</div>
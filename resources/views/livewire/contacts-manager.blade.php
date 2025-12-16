<div class="space-y-4">
    @php
        use App\Enums\GenderOptions;
    @endphp
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
            <x-native-select
                label="Per page"
                wire:model.live="perPage"
                :options="['5' => 5, '10' => 10, '25' => 25, '50' => 50]"
            />
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            @if($search || !empty($selectedGenders))
                <x-button flat negative label="Clear Filters" icon="x-mark" wire:click="clearFilters" />
            @endif

            <div class="w-full sm:w-[500px]">
                <x-input icon="magnifying-glass" placeholder="Search in name, email, phone, custom fields (including merged)" wire:model.live.debounce.400ms="search" class="w-full" />
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @foreach(GenderOptions::cases() as $gender)
                    <x-checkbox 
                        id="gender-{{ $gender->value }}"
                        label="{{ $gender->label() }}" 
                        value="{{ $gender->value }}"
                        wire:model.live="selectedGenders" 
                    />
                @endforeach
            </div>
        </div>
    </div>

    <div class="hidden sm:block">
        <div class="w-full overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
            <table class="w-full table-auto divide-y divide-neutral-200 dark:divide-neutral-700">
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
                                        {{ collect($mergedListDisplays[$contact->id]['names'] ?? [$contact->name])->implode(' / ') }}
                                    </td>
                                    <td class="whitespace-normal px-4 py-3 text-sm text-neutral-600 dark:text-neutral-300">
                                        {{ collect($mergedListDisplays[$contact->id]['emails'] ?? [])->pluck('value')->implode(', ') }}
                                    </td>
                                    <td class="whitespace-normal px-4 py-3 text-sm text-neutral-600 dark:text-neutral-300">
                                        {{ collect($mergedListDisplays[$contact->id]['phones'] ?? [])->pluck('value')->implode(', ') }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-600 dark:text-neutral-300">
                                        {{ $contact->gender instanceof GenderOptions ? $contact->gender->label() : '' }}
                                    </td>

                                    <td class="whitespace-normal px-4 py-3 text-sm text-neutral-600 dark:text-neutral-300">
                                        {{ collect($mergedListDisplays[$contact->id]['custom_fields'] ?? [])->map(function ($field) {
                                            $values = collect($field['values'])->pluck('value')->unique()->implode(', ');
                                            return $field['field_name'] . ': ' . $values;
                                        })->implode(', ') }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $contact->created_at->diffForHumans() }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-right">
                                        @if($contact->secondaryContacts->isNotEmpty())
                                            <x-mini-button rounded warning icon="link-slash"
                                                wire:click="confirmUnmerge({{ $contact->id }})"
                                                title="Unmerge Contacts"
                                            />
                                        @endif
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
    </div>

    <div class="space-y-3 sm:hidden">
        @forelse($contacts as $contact)
            <div class="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            {{ collect($mergedListDisplays[$contact->id]['names'] ?? [$contact->name])->implode(' / ') }}
                        </div>
                        <div class="text-sm text-neutral-600 dark:text-neutral-300">
                            {{ collect($mergedListDisplays[$contact->id]['emails'] ?? [])->pluck('value')->implode(', ') }}
                        </div>
                        <div class="text-sm text-neutral-600 dark:text-neutral-300">
                            {{ collect($mergedListDisplays[$contact->id]['phones'] ?? [])->pluck('value')->implode(', ') }}
                        </div>
                        <div class="text-sm text-neutral-600 dark:text-neutral-300">
                            {{ $contact->gender instanceof GenderOptions ? $contact->gender->label() : '' }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($contact->secondaryContacts->isNotEmpty())
                            <x-mini-button rounded warning icon="link-slash"
                                wire:click="confirmUnmerge({{ $contact->id }})"
                                title="Unmerge Contacts"
                            />
                        @endif
                        <x-mini-button rounded info icon="eye"
                            wire:click="openViewModal({{ $contact->id }})"
                        />
                        <x-mini-button rounded primary icon="pencil"
                            wire:click="$dispatch('edit-contact', { id: {{ $contact->id }} })"
                        />
                        <x-mini-button rounded negative icon="trash"
                            wire:click="confirmDelete({{ $contact->id }})"
                        />
                    </div>
                </div>

                @if(!empty($mergedListDisplays[$contact->id]['custom_fields']))
                    <div class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">
                        <span class="font-medium text-neutral-700 dark:text-neutral-200">Custom Fields:</span>
                        {{ collect($mergedListDisplays[$contact->id]['custom_fields'])->map(function ($field) {
                            $values = collect($field['values'])->pluck('value')->unique()->implode(', ');
                            return $field['field_name'] . ': ' . $values;
                        })->implode(', ') }}
                    </div>
                @endif

                <div class="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                    Added {{ $contact->created_at->diffForHumans() }}
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-neutral-200 bg-white p-4 text-center text-sm text-neutral-500 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-400">
                No contacts found.
            </div>
        @endforelse
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-2">Emails</h4>
                        <div class="space-y-2">
                            @foreach($viewingMergedDisplay['emails'] ?? [] as $email)
                                <div class="flex items-center gap-2 text-sm text-gray-900 dark:text-gray-100">
                                    <span>{{ $email['value'] }}</span>
                                    <x-badge sm outline label="from {{ $email['source'] }}" />
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-2">Phone Numbers</h4>
                        <div class="space-y-2">
                            @foreach($viewingMergedDisplay['phones'] ?? [] as $phone)
                                <div class="flex items-center gap-2 text-sm text-gray-900 dark:text-gray-100">
                                    <span>{{ $phone['value'] }}</span>
                                    <x-badge sm outline label="from {{ $phone['source'] }}" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if(!empty($viewingMergedDisplay['custom_fields']))
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-2">Merged Custom Fields</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($viewingMergedDisplay['custom_fields'] as $field)
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $field['field_name'] }}</label>
                                    @foreach($field['values'] as $value)
                                        <div class="flex items-center gap-2 text-sm text-gray-900 dark:text-gray-100">
                                            <span>{{ $value['value'] }}</span>
                                            <x-badge sm outline label="from {{ $value['source'] }}" />
                                            @if($value['is_searchable'])
                                                <x-badge sm outline positive label="Searchable" />
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!empty($viewingMergedDisplay['secondary_tree']))
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-3">
                        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100">Secondary Contacts</h4>
                        <x-contact-tree :nodes="$viewingMergedDisplay['secondary_tree']" />
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

    {{-- Merge Contacts Modal --}}
    @if($mergeModalOpen)
        <x-modal-card title="Merge Contacts" wire:model="mergeModalOpen">
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-select
                        label="Master Contact"
                        placeholder="Select master contact"
                        wire:model.live="mergeMasterId"
                    >
                        @foreach($allContacts as $option)
                            <x-select.option
                                label="{{ $option->name }}{{ in_array($option->id, $mergeLockedIds, true) ? ' (merged)' : '' }}"
                                value="{{ $option->id }}"
                            />
                        @endforeach
                    </x-select>

                    <x-select
                        label="Secondary Contact"
                        placeholder="Select secondary contact"
                        wire:model.live="mergeSecondaryId"
                    >
                        @foreach($allContacts as $option)
                            <x-select.option
                                label="{{ $option->name }}{{ in_array($option->id, $mergeLockedIds, true) ? ' (merged)' : '' }}"
                                value="{{ $option->id }}"
                            />
                        @endforeach
                    </x-select>
                </div>

                @if($mergePreview)
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-3">
                                <h4 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-1">Master</h4>
                                <div class="text-sm text-neutral-700 dark:text-neutral-300">{{ $mergePreview['master']['name'] }}</div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ $mergePreview['master']['email'] }}</div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ $mergePreview['master']['phone'] }}</div>
                            </div>

                            <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-3">
                                <h4 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-1">Secondary</h4>
                                <div class="text-sm text-neutral-700 dark:text-neutral-300">{{ $mergePreview['secondary']['name'] }}</div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ $mergePreview['secondary']['email'] }}</div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ $mergePreview['secondary']['phone'] }}</div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-dashed border-neutral-300 dark:border-neutral-700 p-3 space-y-2">
                            <h4 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">Preview (display only)</h4>
                            <div class="flex flex-wrap gap-2 text-sm text-neutral-700 dark:text-neutral-300">
                                @foreach($mergePreview['merged']['emails'] as $email)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-neutral-100 dark:bg-neutral-800 px-2 py-1">
                                        {{ $email['value'] }}
                                        <x-badge sm outline label="{{ $email['source'] }}" />
                                    </span>
                                @endforeach
                            </div>
                            <div class="flex flex-wrap gap-2 text-sm text-neutral-700 dark:text-neutral-300">
                                @foreach($mergePreview['merged']['phones'] as $phone)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-neutral-100 dark:bg-neutral-800 px-2 py-1">
                                        {{ $phone['value'] }}
                                        <x-badge sm outline label="{{ $phone['source'] }}" />
                                    </span>
                                @endforeach
                            </div>
                            <div class="space-y-1 text-sm text-neutral-700 dark:text-neutral-300">
                                @foreach($mergePreview['merged']['custom_fields'] as $field)
                                    <div class="flex flex-wrap gap-2 items-center">
                                        <span class="font-semibold">{{ $field['field_name'] }}:</span>
                                        @foreach($field['values'] as $value)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-neutral-100 dark:bg-neutral-800 px-2 py-1">
                                                {{ $value['value'] }}
                                                <x-badge sm outline label="{{ $value['source'] }}" />
                                            </span>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="rounded-md border border-dashed border-neutral-300 dark:border-neutral-700 p-3 text-sm text-neutral-600 dark:text-neutral-300">
                        Select a master and secondary contact, then click "Preview" to see the combined view before confirming.
                    </div>
                @endif
            </div>

            <x-slot name="footer">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 w-full">
                    <x-button flat label="Cancel" x-on:click="close" />
                    <div class="flex items-center gap-2">
                        <x-button
                            outline
                            label="Preview"
                            wire:click="prepareMergePreview"
                            :disabled="!$mergeMasterId || !$mergeSecondaryId"
                        />

                        @if($mergePreview)
                            {{-- Active, clickable confirm with normal tooltip --}}
                            <flux:button
                                variant="primary"
                                wire:click="confirmMerge"
                                tooltip="Confirm and link the selected contacts."
                            >
                                Confirm Merge
                            </flux:button>
                        @else
                            {{-- Visually disabled, non-clickable, but still hoverable for tooltip --}}
                            <flux:button
                                variant="primary"
                                type="button"
                                class="opacity-60 cursor-not-allowed"
                                tooltip="Select a master and secondary contact, then click Preview to enable this action."
                            >
                                Confirm Merge
                            </flux:button>
                        @endif
                    </div>
                </div>
            </x-slot>
        </x-modal-card>
    @endif
</div>
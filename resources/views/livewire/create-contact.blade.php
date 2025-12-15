<div>
    <x-button label="Add Contact" primary wire:click="openModal" />

    <x-modal-card title="Add New Contact" wire:model="modalOpen">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-input label="Name" placeholder="Full Name" wire:model="name" />

            <x-input label="Email" placeholder="example@mail.com" wire:model="email" />

            <x-input label="Phone" placeholder="+1 123 456 7890" wire:model="phone" />

            <x-select label="Gender" placeholder="Select Gender" wire:model="gender">
                @foreach($genders as $gender)
                    <x-select.option label="{{ $gender->label() }}" value="{{ $gender->value }}" />
                @endforeach
            </x-select>

            <div class="col-span-1 sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1">Profile Image</label>
                <input type="file" wire:model="profile_image"
                    class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" />
                @error('profile_image') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-1 sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1">Additional File</label>
                <input type="file" wire:model="additional_file"
                    class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" />
                @error('additional_file') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-1 sm:col-span-2">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Custom Fields</h3>
                    <x-mini-button rounded icon="plus" positive wire:click="addCustomField" />
                </div>

                <div class="space-y-3">
                    @foreach($customFields as $index => $field)
                        <div class="flex gap-2 items-start">
                            <div class="w-1/2">
                                <x-input placeholder="Field Name (e.g. Birthday)"
                                    wire:model="customFields.{{ $index }}.key" />
                            </div>
                            <div class="w-1/2">
                                <x-input placeholder="Value" wire:model="customFields.{{ $index }}.value" />
                            </div>
                            <x-mini-button rounded icon="trash" negative wire:click="removeCustomField({{ $index }})" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex justify-between gap-x-4">
                <x-button flat label="Cancel" x-on:click="close" />
                <x-button primary label="Save" wire:click="save" />
            </div>
        </x-slot>
    </x-modal-card>
</div>
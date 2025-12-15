<div>
    <x-modal-card title="Edit Contact" wire:model="modalOpen">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-input label="Name" placeholder="Full Name" wire:model="name" />

            <x-input label="Email" placeholder="example@mail.com" wire:model="email" />

            <x-input label="Phone" placeholder="+1 123 456 7890" wire:model="phone" />

            <x-select label="Gender" placeholder="Select Gender" wire:model="gender">
                @foreach($genders as $gender)
                    <x-select.option label="{{ $gender->label() }}" value="{{ $gender->value }}" />
                @endforeach
            </x-select>

            <!-- Profile Image -->
            <div class="col-span-1 sm:col-span-2">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Profile Image</label>

                    @if($existingProfileImage && !$newProfileImage)
                        <x-mini-button rounded negative icon="trash" wire:click="deleteFileConfirmation('profile_image')"
                            title="Delete Existing Image" />
                    @endif
                </div>

                @if($newProfileImage)
                    <div class="mb-2">
                        <img src="{{ $newProfileImage->temporaryUrl() }}" class="h-20 w-20 rounded-full object-cover" />
                    </div>
                @elseif($existingProfileImage)
                    <div class="mb-2">
                        <img src="{{ Storage::url($existingProfileImage) }}" class="h-20 w-20 rounded-full object-cover" />
                    </div>
                @endif

                <input type="file" wire:model="newProfileImage"
                    class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                @error('newProfileImage') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            <!-- Additional File -->
            <div class="col-span-1 sm:col-span-2">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Additional File</label>

                    @if($existingAdditionalFile && !$newAdditionalFile)
                        <x-mini-button rounded negative icon="trash" wire:click="deleteFileConfirmation('additional_file')"
                            title="Delete Existing File" />
                    @endif
                </div>

                @if($existingAdditionalFile && !$newAdditionalFile)
                    <div class="mb-2 text-sm text-gray-600 dark:text-gray-300">
                        Current file: <a href="{{ Storage::url($existingAdditionalFile) }}" target="_blank"
                            class="text-blue-600 hover:underline">View File</a>
                    </div>
                @endif

                <input type="file" wire:model="newAdditionalFile"
                    class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                @error('newAdditionalFile') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            <!-- Custom Fields -->
            <div class="col-span-1 sm:col-span-2">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Custom Fields</h3>
                    <x-mini-button rounded icon="plus" positive wire:click="addCustomField" />
                </div>

                <div class="space-y-3">
                    @foreach($customFields as $index => $field)
                        <div class="flex items-end gap-2" wire:key="field-{{ $index }}">
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
                <x-button primary label="Save Changes" wire:click="save" />
            </div>
        </x-slot>
    </x-modal-card>
</div>
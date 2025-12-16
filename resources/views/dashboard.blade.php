<x-layouts.app :title="__('Contacts')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">Contacts</h1>
            <div class="flex items-center gap-3">
                <livewire:create-contact />
                <x-button
                    icon="arrow-path"
                    primary
                    label="Merge Contact"
                    x-data
                    x-on:click="Livewire.dispatch('open-merge-modal')"
                />
            </div>
        </div>
        <div class="w-full">
            <livewire:contacts-manager />
        </div>
    </div>
</x-layouts.app>
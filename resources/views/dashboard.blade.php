<x-layouts.app :title="__('Contacts')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">Contacts</h1>
            <livewire:create-contact />
        </div>
        <div class="w-full">
            <livewire:contacts-manager />
        </div>
    </div>
</x-layouts.app>
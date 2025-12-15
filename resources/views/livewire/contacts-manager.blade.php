<div class="space-y-4">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 w-full max-w-md">
            <x-input icon="magnifying-glass" placeholder="Search name or email" wire:model.live.debounce.400ms="search" class="w-full" />
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
                                    {{ $contact->gender instanceof \App\Enums\GenderOptions ? $contact->gender->label() : '' }}
                                </td>

                                <td class="whitespace-normal px-4 py-3 text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ $contact->fields->map(function ($f) {
                    return $f->field_name . ': ' . $f->field_value; })->implode(', ') }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $contact->created_at->diffForHumans() }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-sm text-right">
                                    <x-mini-button rounded negative icon="trash"
                                        wire:click="deleteContact({{ $contact->id }})"
                                        wire:confirm="Are you sure you want to delete this contact?"
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
            {{ $contacts->links() }}
        </div>
    </div>
</div>
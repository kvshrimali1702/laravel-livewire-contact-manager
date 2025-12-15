<div class="space-y-4">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <label class="relative block">
                <span class="sr-only">Search</span>
                <input wire:model.live.debounce.400ms="search"
                    class="block w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-900 placeholder:text-neutral-400 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                    placeholder="Search name or email">
            </label>
        </div>
        <div class="flex items-center gap-2">
            <label class="flex items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300">Per page
                <select wire:model.live="perPage"
                    class="ml-2 rounded-md border border-neutral-200 bg-white px-2 py-1 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </label>
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
                                    <button
                                        onclick="if (confirm('Are you sure you want to delete this contact?')) { window.Livewire.find(document.querySelectorAll('[wire\\:id]')[0].getAttribute('wire:id')).call('deleteContact', {{ $contact->id }}) }"
                                        title="Delete"
                                        class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-50 px-2.5 py-1 text-sm font-medium text-red-600 hover:bg-red-100 dark:bg-red-900/40 dark:text-red-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20"
                                            fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd"
                                                d="M6 2a1 1 0 00-1 1v1H3.5A1.5 1.5 0 002 5.5v.5a.5.5 0 000 1V16a2 2 0 002 2h8a2 2 0 002-2V7a.5.5 0 000-1v-.5A1.5 1.5 0 0016.5 4H15V3a1 1 0 00-1-1H6zm2 5a.5.5 0 011 0v7a.5.5 0 01-1 0V7zm3 0a.5.5 0 011 0v7a.5.5 0 01-1 0V7z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
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
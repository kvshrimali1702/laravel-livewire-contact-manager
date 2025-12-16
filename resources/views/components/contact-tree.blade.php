@props(['nodes'])

@foreach($nodes as $node)
    <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-3 space-y-2">
        <div class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ $node['name'] }}</div>
        <div class="text-sm text-neutral-700 dark:text-neutral-300">{{ $node['email'] }}</div>
        <div class="text-sm text-neutral-700 dark:text-neutral-300">{{ $node['phone'] }}</div>
        @if(!empty($node['fields']))
            <div class="text-xs text-neutral-600 dark:text-neutral-400">
                Custom: {{ collect($node['fields'])->map(fn($f) => $f['name'] . ': ' . $f['value'])->implode(', ') }}
            </div>
        @endif
        @if(!empty($node['children']))
            <div class="mt-2 space-y-2">
                <x-contact-tree :nodes="$node['children']" />
            </div>
        @endif
    </div>
@endforeach
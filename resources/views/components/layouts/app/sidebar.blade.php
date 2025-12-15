<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')" class="grid">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Contacts') }}</flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                {{ __('Documentation') }}
                </flux:navlist.item>
            </flux:navlist>

            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        {{-- Global WireUI notifications container (toasts) --}}
        <x-notifications />
        
        {{-- Global alert listener: listens for `app:alert` browser events and renders
        a WireUI-styled alert using Tailwind classes so it works client-side
        (can be triggered from Livewire via dispatchBrowserEvent). --}}
        <div x-data="globalAlert()" x-init="init()">
            <template x-if="open">
                <div class="fixed inset-0 flex items-start justify-center px-4 pt-6 pointer-events-none sm:p-5 sm:pt-4">
                    <div x-show="open" x-transition.opacity class="pointer-events-auto w-full max-w-lg">
                        <div :class="containerClasses" class="rounded-md shadow-lg border p-4">
                            <div class="flex items-start">
                                <div :class="iconClasses + ' shrink-0 mr-3'" x-html="iconHtml"></div>
                                <div class="flex-1">
                                    <div class="font-semibold" x-text="title"></div>
                                    <div class="mt-1 text-sm" x-text="description"></div>
                                </div>
                                <div class="ml-4 shrink-0">
                                    <button class="text-sm font-medium focus:outline-none" @click="close()">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <wireui:scripts />
<script>
    function globalAlert() {
        return {
            open: false,
            title: null,
            description: null,
            color: 'primary',
            timeout: 3000,
            timer: null,
            init() {
                window.addEventListener('app:alert', (e) => this.show(e.detail || {}));
            },
            show(payload) {
                this.title = payload.title || '';
                this.description = payload.description || '';
                this.color = payload.color || 'primary';
                this.open = true;
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.close(), this.timeout);
            },
            close() {
                this.open = false;
                clearTimeout(this.timer);
            },
            get containerClasses() {
                // map logical colors to Tailwind classes for light/dark
                const map = {
                    primary: 'bg-white border-gray-200 text-gray-900 dark:bg-secondary-800 dark:border-secondary-700 dark:text-secondary-100',
                    secondary: 'bg-gray-50 border-gray-200 text-gray-900 dark:bg-secondary-800 dark:border-secondary-700 dark:text-secondary-100',
                    positive: 'bg-green-50 border-green-200 text-green-900 dark:bg-green-900/30 dark:border-green-700 dark:text-green-100',
                    negative: 'bg-red-50 border-red-200 text-red-900 dark:bg-red-900/30 dark:border-red-700 dark:text-red-100',
                    warning: 'bg-yellow-50 border-yellow-200 text-yellow-900 dark:bg-yellow-900/25 dark:border-yellow-700 dark:text-yellow-100',
                    info: 'bg-sky-50 border-sky-200 text-sky-900 dark:bg-sky-900/25 dark:border-sky-700 dark:text-sky-100'
                };
                return map[this.color] || map.primary;
            },
            get iconClasses() {
                const map = {
                    primary: 'text-gray-500 dark:text-secondary-300',
                    secondary: 'text-gray-500 dark:text-secondary-300',
                    positive: 'text-green-500 dark:text-green-300',
                    negative: 'text-red-500 dark:text-red-300',
                    warning: 'text-yellow-500 dark:text-yellow-300',
                    info: 'text-sky-500 dark:text-sky-300'
                };
                return map[this.color] || map.primary;
            },
            get iconHtml() {
                // simple SVG icons per type (kept minimal)
                const icons = {
                    positive: '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
                    negative: '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
                    warning: '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a1 1 0 00.86 1.5h18.64a1 1 0 00.86-1.5L13.71 3.86a1 1 0 00-1.71 0z"/></svg>',
                    info: '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 12a9 9 0 110-18 9 9 0 010 18z"/></svg>',
                    primary: '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01"/></svg>',
                    secondary: '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01"/></svg>'
                };
                return icons[this.color] || icons.primary;
            }
        }
    }
</script>
        @fluxScripts
    </body>
</html>

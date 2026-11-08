<x-layouts.app.sidebar>
    <flux:main>
        <div class="mb-4 flex justify-end">
            <livewire:language-switcher />
        </div>

        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>

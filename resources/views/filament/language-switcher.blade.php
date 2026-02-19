<div class="flex items-center gap-2 px-4">
    <x-filament::dropdown placement="bottom-end">
        <x-slot name="trigger">
            <x-filament::icon-button
                icon="heroicon-o-language"
                label="{{ app()->getLocale() === 'fr' ? 'Français' : 'English' }}"
            />
        </x-slot>

        <x-filament::dropdown.list>
            <x-filament::dropdown.list.item
                :href="url()->current() . '?lang=fr'"
                tag="a"
                icon="heroicon-o-flag"
                :active="app()->getLocale() === 'fr'"
            >
                Français
            </x-filament::dropdown.list.item>

            <x-filament::dropdown.list.item
                :href="url()->current() . '?lang=en'"
                tag="a"
                icon="heroicon-o-flag"
                :active="app()->getLocale() === 'en'"
            >
                English
            </x-filament::dropdown.list.item>
        </x-filament::dropdown.list>
    </x-filament::dropdown>
</div>

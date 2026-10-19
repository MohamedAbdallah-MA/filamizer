@php
    use DealArt\FilamentStudio\Navigation\StudioNavigation;

    $statePath = $getStatePath();
    $catalog = $getCatalog();

    $strings = [
        'ungrouped' => __('Without group (top of the sidebar)'),
        'newGroup' => __('New group'),
        'untitled' => __('Untitled group'),
        'emptyGroup' => __('Drop items here'),
        'confirmReset' => __('Reset the sidebar to its default layout? Your changes are discarded when you save.'),
        'confirmDelete' => __('Delete this group? Its items move to the top of the sidebar.'),
        'localeNames' => ['en' => __('English'), 'ar' => __('Arabic')],
    ];
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    {{-- Alpine owns this DOM (x-for + Sortable); Livewire only syncs the entangled state. --}}
    <div
        wire:ignore
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            catalog: @js($catalog['items']),
            defaults: @js($getDefaultLayout()),
            icons: @js($getIconHtml()),
            locales: @js(StudioNavigation::locales()),
            locale: @js(app()->getLocale()),
            strings: @js($strings),
            ungrouped: @js(StudioNavigation::UNGROUPED),
            editingItem: null,
            editingGroup: null,
            pickerFor: null,
            iconSearch: '',

            openPicker(item) {
                this.pickerFor = item.key
                this.iconSearch = ''
                this.$nextTick(() => this.$refs.iconSearch?.focus())
            },

            closePicker() {
                this.pickerFor = null
            },

            pickedItem() {
                return (this.state?.items ?? []).find((item) => item.key === this.pickerFor)
            },

            currentIcon() {
                const item = this.pickedItem()

                return item ? (item.icon || this.catalog[item.key]?.icon) : null
            },

            iconMatches(name) {
                const query = this.iconSearch.trim().toLowerCase().replaceAll(' ', '-')

                return ! query || name.includes(query)
            },

            chooseIcon(name, button) {
                const item = this.pickedItem()

                if (! item) return

                // Keep the rendered SVG so the row updates right away, before saving.
                this.icons[name] ??= button.querySelector('svg')?.outerHTML
                item.icon = name === this.catalog[item.key]?.icon ? null : name
                this.closePicker()
            },

            init() {
                this.$nextTick(() => this.makeGroupsSortable())
            },

            itemsOf(group) {
                return (this.state?.items ?? []).filter((item) => item.group === group)
            },

            pick(labels) {
                if (labels?.[this.locale]) return labels[this.locale]

                return Object.values(labels ?? {}).find((label) => label) ?? ''
            },

            itemLabel(item) {
                return this.pick(item.labels) || this.pick(this.catalog[item.key]?.labels) || item.key
            },

            groupLabel(group) {
                return this.pick(group.labels) || this.strings.untitled
            },

            iconFor(item) {
                return this.icons[item.icon || this.catalog[item.key]?.icon] ?? null
            },

            // Sortable moves DOM nodes itself, which would fight Alpine's x-for. Put the
            // node back where it was, then update the state and let Alpine re-render.
            revert(event) {
                const { item, from, oldDraggableIndex } = event
                const siblings = [...from.querySelectorAll(':scope > [data-studio-sortable]')].filter((el) => el !== item)

                from.insertBefore(item, siblings[oldDraggableIndex] ?? null)
            },

            makeItemsSortable(list) {
                if (list.sortable) return

                list.sortable = window.Sortable.create(list, {
                    group: 'studio-nav-items',
                    handle: '[data-studio-handle]',
                    draggable: '[data-studio-sortable]',
                    animation: 180,
                    ghostClass: 'fi-studio-nav-ghost',
                    onEnd: (event) => {
                        const fromGroup = event.from.dataset.group
                        const toGroup = event.to.dataset.group

                        if (fromGroup === toGroup && event.oldDraggableIndex === event.newDraggableIndex) return

                        this.revert(event)
                        this.moveItem(fromGroup, event.oldDraggableIndex, toGroup, event.newDraggableIndex)
                    },
                })
            },

            makeGroupsSortable() {
                const list = this.$refs.groups

                if (! list || list.sortable) return

                list.sortable = window.Sortable.create(list, {
                    handle: '[data-studio-group-handle]',
                    draggable: '[data-studio-sortable]',
                    animation: 180,
                    ghostClass: 'fi-studio-nav-ghost',
                    onEnd: (event) => {
                        if (event.oldDraggableIndex === event.newDraggableIndex) return

                        this.revert(event)

                        const groups = [...this.state.groups]
                        const [moved] = groups.splice(event.oldDraggableIndex, 1)
                        groups.splice(event.newDraggableIndex, 0, moved)
                        this.state.groups = groups
                    },
                })
            },

            moveItem(fromGroup, fromIndex, toGroup, toIndex) {
                const items = [...this.state.items]
                const moving = items.filter((item) => item.group === fromGroup)[fromIndex]

                if (! moving) return

                items.splice(items.indexOf(moving), 1)

                const target = items.filter((item) => item.group === toGroup)
                const updated = { ...moving, group: toGroup }

                if (target[toIndex]) {
                    items.splice(items.indexOf(target[toIndex]), 0, updated)
                } else if (target.length) {
                    items.splice(items.indexOf(target[target.length - 1]) + 1, 0, updated)
                } else {
                    items.push(updated)
                }

                this.state.items = items
            },

            addGroup() {
                const id = 'g_' + Math.random().toString(36).slice(2, 10)
                const labels = Object.fromEntries(this.locales.map((locale) => [locale, '']))
                labels[this.locale] = this.strings.newGroup

                this.state.groups = [...this.state.groups, { id, labels, collapsed: false }]
                this.editingGroup = id
            },

            removeGroup(id) {
                if (! confirm(this.strings.confirmDelete)) return

                this.state.items = this.state.items.map((item) => item.group === id ? { ...item, group: this.ungrouped } : item)
                this.state.groups = this.state.groups.filter((group) => group.id !== id)
            },

            reset() {
                if (! confirm(this.strings.confirmReset)) return

                this.state = JSON.parse(JSON.stringify(this.defaults))
                this.editingItem = null
                this.editingGroup = null
            },
        }"
        class="fi-studio-nav"
    >
        {{-- One shared icon picker, moved to <body> so it overlays everything. --}}
        <template x-teleport="body">
            <div
                class="fi-studio-icon-picker"
                x-show="pickerFor"
                x-cloak
                x-transition.opacity
                x-on:keydown.escape.window="closePicker()"
                role="dialog"
                aria-modal="true"
                aria-label="{{ __('Choose icon') }}"
            >
                <div class="fi-studio-icon-picker-backdrop" x-on:click="closePicker()"></div>

                <div class="fi-studio-icon-picker-panel">
                    <header class="fi-studio-icon-picker-header">
                        <span class="fi-studio-nav-group-title">{{ __('Choose icon') }}</span>

                        <button type="button" class="fi-studio-nav-icon-btn" x-on:click="closePicker()" title="{{ __('Close') }}">
                            <x-filament::icon icon="heroicon-m-x-mark" />
                        </button>
                    </header>

                    <input
                        type="search"
                        class="fi-studio-nav-input"
                        x-ref="iconSearch"
                        x-model="iconSearch"
                        dir="ltr"
                        placeholder="{{ __('Search icons (in English), e.g. user, chart, bell') }}"
                    />

                    <div class="fi-studio-icon-grid">
                        @foreach ($getIconOptions() as $name => $html)
                            <button
                                type="button"
                                class="fi-studio-icon-option"
                                title="{{ str($name)->after('heroicon-o-')->replace('-', ' ') }}"
                                x-show="iconMatches(@js($name))"
                                x-on:click="chooseIcon(@js($name), $el)"
                                :class="{ 'fi-studio-icon-option-active': currentIcon() === @js($name) }"
                            >
                                {!! $html !!}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </template>

        <div class="fi-studio-nav-toolbar">
            <x-filament::button size="sm" icon="heroicon-m-plus" x-on:click="addGroup()">
                {{ __('Add group') }}
            </x-filament::button>

            <x-filament::button size="sm" color="gray" icon="heroicon-m-arrow-uturn-left" x-on:click="reset()">
                {{ __('Reset to default') }}
            </x-filament::button>
        </div>

        {{-- Items without a group sit at the very top of the sidebar. --}}
        <section class="fi-studio-nav-group fi-studio-nav-group-pinned">
            <header class="fi-studio-nav-group-header">
                <x-filament::icon icon="heroicon-m-bookmark" class="fi-studio-nav-muted-icon" />
                <span class="fi-studio-nav-group-title" x-text="strings.ungrouped"></span>
            </header>

            <div class="fi-studio-nav-items" data-group="{{ StudioNavigation::UNGROUPED }}" x-init="makeItemsSortable($el)">
                <template x-for="item in itemsOf(ungrouped)" :key="item.key">
                    @include('filament-studio::navigation-editor-item')
                </template>

                <p class="fi-studio-nav-empty" x-show="! itemsOf(ungrouped).length" x-text="strings.emptyGroup"></p>
            </div>
        </section>

        <div class="fi-studio-nav-groups" x-ref="groups">
            <template x-for="group in state?.groups ?? []" :key="group.id">
                <section class="fi-studio-nav-group" data-studio-sortable>
                    <header class="fi-studio-nav-group-header">
                        <button type="button" class="fi-studio-nav-handle" data-studio-group-handle title="{{ __('Drag to reorder') }}">
                            <x-filament::icon icon="heroicon-m-bars-2" />
                        </button>

                        <span class="fi-studio-nav-group-title" x-text="groupLabel(group)"></span>

                        <label class="fi-studio-nav-check">
                            <input type="checkbox" x-model="group.collapsed" />
                            <span>{{ __('Collapsed by default') }}</span>
                        </label>

                        <button type="button" class="fi-studio-nav-icon-btn" x-on:click="editingGroup = editingGroup === group.id ? null : group.id" title="{{ __('Rename') }}">
                            <x-filament::icon icon="heroicon-m-pencil-square" />
                        </button>

                        <button type="button" class="fi-studio-nav-icon-btn fi-studio-nav-danger" x-on:click="removeGroup(group.id)" title="{{ __('Delete group') }}">
                            <x-filament::icon icon="heroicon-m-trash" />
                        </button>
                    </header>

                    <div class="fi-studio-nav-editor" x-show="editingGroup === group.id" x-collapse>
                        <template x-for="code in locales" :key="code">
                            <label class="fi-studio-nav-field">
                                <span x-text="strings.localeNames[code] ?? code"></span>
                                <input type="text" class="fi-studio-nav-input" maxlength="60" x-model="group.labels[code]" :dir="code === 'ar' ? 'rtl' : 'ltr'" />
                            </label>
                        </template>
                    </div>

                    <div class="fi-studio-nav-items" :data-group="group.id" x-init="makeItemsSortable($el)">
                        <template x-for="item in itemsOf(group.id)" :key="item.key">
                            @include('filament-studio::navigation-editor-item')
                        </template>

                        <p class="fi-studio-nav-empty" x-show="! itemsOf(group.id).length" x-text="strings.emptyGroup"></p>
                    </div>
                </section>
            </template>
        </div>
    </div>
</x-dynamic-component>

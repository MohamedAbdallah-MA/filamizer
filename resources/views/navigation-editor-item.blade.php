{{-- A sidebar item row inside the navigation editor (rendered inside an Alpine x-for). --}}
<div class="fi-studio-nav-item" data-studio-sortable :class="{ 'fi-studio-nav-item-hidden': item.hidden }">
    <div class="fi-studio-nav-item-row">
        <button type="button" class="fi-studio-nav-handle" data-studio-handle title="{{ __('Drag to move') }}">
            <x-filament::icon icon="heroicon-m-bars-2" />
        </button>

        <span class="fi-studio-nav-item-icon">
            <template x-if="iconFor(item)">
                <span x-html="iconFor(item)"></span>
            </template>
            <template x-if="! iconFor(item)">
                <span class="fi-studio-nav-icon-dot"></span>
            </template>
        </span>

        <span class="fi-studio-nav-item-label" x-text="itemLabel(item)"></span>

        <button
            type="button"
            class="fi-studio-nav-icon-btn"
            x-on:click="item.hidden = ! item.hidden"
            :title="item.hidden ? @js(__('Show in sidebar')) : @js(__('Hide from sidebar'))"
        >
            <span x-show="! item.hidden"><x-filament::icon icon="heroicon-m-eye" /></span>
            <span x-show="item.hidden" x-cloak><x-filament::icon icon="heroicon-m-eye-slash" /></span>
        </button>

        <button type="button" class="fi-studio-nav-icon-btn" x-on:click="editingItem = editingItem === item.key ? null : item.key" title="{{ __('Edit') }}">
            <x-filament::icon icon="heroicon-m-pencil-square" />
        </button>
    </div>

    <div class="fi-studio-nav-editor" x-show="editingItem === item.key" x-collapse>
        <template x-for="code in locales" :key="code">
            <label class="fi-studio-nav-field">
                <span x-text="strings.localeNames[code] ?? code"></span>
                <input
                    type="text"
                    class="fi-studio-nav-input"
                    maxlength="60"
                    x-model="item.labels[code]"
                    :placeholder="catalog[item.key]?.labels?.[code] ?? ''"
                    :dir="code === 'ar' ? 'rtl' : 'ltr'"
                />
            </label>
        </template>

        <div class="fi-studio-nav-field">
            <span>{{ __('Icon') }}</span>

            <div class="fi-studio-nav-icon-choice">
                <button type="button" class="fi-studio-nav-icon-pick" x-on:click="openPicker(item)">
                    <span class="fi-studio-nav-item-icon" x-html="iconFor(item) ?? ''"></span>
                    <span>{{ __('Choose icon') }}</span>
                </button>

                <button type="button" class="fi-studio-nav-link" x-show="item.icon" x-on:click="item.icon = null">
                    {{ __('Use default') }}
                </button>
            </div>
        </div>

        <p class="fi-studio-nav-hint">{{ __('Leave a name empty to keep the default.') }}</p>
    </div>
</div>

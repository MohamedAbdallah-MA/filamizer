@php
    $statePath = $getStatePath();
    $isRtl = __('filament-panels::layout.direction') === 'rtl';
    $imageUrl = \DealArt\FilamentStudio\Support\StudioTheme::backdropImageUrl();
    $darkImageUrl = \DealArt\FilamentStudio\Support\StudioTheme::backdropImageUrl(dark: true);
@endphp

{{-- A small dashboard drawn with the theme being edited (light or dark), whatever mode the page is in. --}}
{{-- No double quotes inside x-data: they would end the attribute. --}}
<div
    x-data="{
        state: {},
        images: { light: @js($imageUrl ? \DealArt\FilamentStudio\Support\StudioTheme::cssUrl($imageUrl) : 'none'), dark: @js($darkImageUrl ? \DealArt\FilamentStudio\Support\StudioTheme::cssUrl($darkImageUrl) : 'none') },
        get imageCss() { return this.images[this.dark ? 'dark' : 'light'] },
        init() {
            this.state = $wire.get('{{ $statePath }}') ?? {}
            this.$watch('$wire.{{ $statePath }}', (value) => this.state = value ?? {})
        },
        get dark() { return this.state.edit_mode === 'dark' },
        pick(key) { return this.state[(this.dark ? 'dark_' : '') + key] },
        hex(value, fallback) { return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(value ?? '') ? value : fallback },
        get skin() { return this.pick('skin') ?? 'classic' },
        get primary() { return this.hex(this.pick('primary'), '#B08C50') },
        get background() { return this.hex(this.pick('background'), this.dark ? '#0C0C0C' : '#FAFAF9') },
        get surface() { return this.hex(this.pick('surface'), this.dark ? '#1A1612' : '#FFFFFF') },
        get input() { return this.hex(this.pick('input'), this.surface) },
        get border() { return this.hex(this.pick('border'), this.dark ? '#342F29' : '#E8E5DF') },
        get readability() { return window.filamentStudioReadability.resolve(this.state, this.dark) },
        get text() { return this.readability.surfaceText },
        get muted() { return this.readability.surfaceMuted },
        get glassAlpha() { return this.readability.glassOpacity },
        get glow() { return Number(this.pick('glow_intensity') ?? 60) },
        canvasStyle() {
            const strength = Number(this.pick('backdrop_intensity') ?? 100) / 100 * (this.dark ? 1.35 : 1)
            return {
                backgroundColor: this.background,
                '--studio-backdrop-strength': strength,
                '--studio-backdrop-intensity': Number(this.pick('backdrop_intensity') ?? 100) / 100,
                '--studio-backdrop-color-1': this.hex(this.pick('backdrop_color_1'), this.primary),
                '--studio-backdrop-color-2': this.hex(this.pick('backdrop_color_2'), '#1A4A7C'),
                '--studio-backdrop-color-3': this.hex(this.pick('backdrop_color_3'), '#1E6B3C'),
                '--studio-backdrop-angle': `${Number(this.pick('backdrop_angle') ?? 135)}deg`,
                '--studio-aurora-duration': `${Number(this.pick('aurora_speed') ?? 14)}s`,
                '--studio-backdrop-image': this.imageCss,
                '--studio-page-scrim': this.readability.pageScrim,
            }
        },
        panelStyle() {
            const style = { backgroundColor: this.surface, color: this.text }

            if (this.skin === 'glass') {
                style.backgroundColor = `color-mix(in oklab, ${this.surface} ${this.glassAlpha}%, transparent)`
                style.backdropFilter = `blur(${this.pick('glass_blur') ?? 16}px) saturate(180%)`
                style.boxShadow = `inset 1px 1px 0 ${this.dark ? 'rgb(255 255 255 / 0.3)' : 'rgb(255 255 255 / 0.9)'}, 0 0 0 1px ${this.dark ? 'rgb(255 255 255 / 0.1)' : 'rgb(255 255 255 / 0.6)'}, 0 10px 24px -12px rgb(0 0 0 / 0.35)`
            } else if (this.skin === 'neon') {
                style.boxShadow = `0 0 0 1px color-mix(in oklab, ${this.primary} 40%, transparent), 0 0 ${Math.round(this.glow / 3)}px -4px color-mix(in oklab, ${this.primary} ${this.glow}%, transparent)`
            } else if (this.skin === 'soft') {
                style.boxShadow = `0 12px 26px -14px rgb(0 0 0 / ${this.dark ? 0.7 : 0.25})`
            } else if (this.skin === 'minimal') {
                style.boxShadow = `0 0 0 1px ${this.border}`
            } else {
                style.boxShadow = `0 0 0 1px ${this.border}, 0 1px 2px rgb(0 0 0 / 0.06)`
            }

            return style
        },
        buttonStyle() {
            return {
                backgroundColor: this.primary,
                boxShadow: this.skin === 'neon' ? `0 0 14px -2px color-mix(in oklab, ${this.primary} ${this.glow}%, transparent)` : 'none',
            }
        },
        activeStyle() {
            return {
                backgroundColor: `color-mix(in oklab, ${this.primary} ${this.dark ? 30 : 18}%, transparent)`,
                color: this.text,
                boxShadow: this.skin === 'neon' ? `inset 0 0 0 1px color-mix(in oklab, ${this.primary} 50%, transparent)` : 'none',
            }
        },
    }"
    class="fi-studio-mini"
>
    <details class="fi-studio-mini-details" wire:ignore.self open>
        <summary class="fi-studio-mini-head">
            <span class="fi-studio-nav-group-title">{{ __('Preview') }}</span>
            <span class="fi-studio-profile-meta" x-text="dark ? @js(__('Dark theme')) : @js(__('Light theme'))"></span>
            <x-filament::icon icon="heroicon-m-chevron-down" class="fi-studio-mini-toggle-icon" />
        </summary>

        <div class="fi-studio-mini-content">
            <div class="fi-studio-mini-canvas" x-bind:style="canvasStyle()"
                x-bind:data-studio-backdrop="pick('backdrop') === 'image' && imageCss === 'none' ? 'none' : (pick('backdrop') ?? 'none')"
                x-bind:data-studio-motion="pick('aurora_animated') === false || pick('aurora_animated') === 0 ? 'off' : 'on'"
                x-bind:data-studio-readable="readability.automatic ? 'auto' : 'manual'"
                dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
                <div class="fi-studio-mini-topbar" x-bind:style="panelStyle()">
                    <span class="fi-studio-mini-logo" x-bind:style="{ color: text }">{{ __('Dashboard') }}</span>
                    <span class="fi-studio-mini-search" x-bind:style="{ backgroundColor: input, boxShadow: `0 0 0 1px ${border}` }"></span>
                </div>

                <div class="fi-studio-mini-body">
                    <div class="fi-studio-mini-sidebar" x-bind:style="panelStyle()">
                        <span class="fi-studio-mini-nav" x-bind:style="activeStyle()">{{ __('Dashboard') }}</span>
                        <span class="fi-studio-mini-nav" x-bind:style="{ color: text }">{{ __('Customers') }}</span>
                        <span class="fi-studio-mini-nav" x-bind:style="{ color: text }">{{ __('Projects') }}</span>
                        <span class="fi-studio-mini-nav" x-bind:style="{ color: text }">{{ __('Settings') }}</span>
                    </div>

                    <div class="fi-studio-mini-main">
                        <div class="fi-studio-mini-stats">
                            <div class="fi-studio-mini-card" x-bind:style="panelStyle()">
                                <span class="fi-studio-mini-label" x-bind:style="{ color: muted }">{{ __('Customers') }}</span>
                                <strong>128</strong>
                                <span class="fi-studio-mini-line" x-bind:style="{ backgroundColor: primary }"></span>
                            </div>
                            <div class="fi-studio-mini-card" x-bind:style="panelStyle()">
                                <span class="fi-studio-mini-label" x-bind:style="{ color: muted }">{{ __('Projects') }}</span>
                                <strong>42</strong>
                                <span class="fi-studio-mini-line" x-bind:style="{ backgroundColor: '#1D9E75' }"></span>
                            </div>
                        </div>

                        <div class="fi-studio-mini-card" x-bind:style="panelStyle()">
                            <span class="fi-studio-mini-field" x-bind:style="{ backgroundColor: input, boxShadow: `0 0 0 1px ${border}` }"></span>
                            <span class="fi-studio-mini-button" x-bind:style="buttonStyle()">{{ __('Save') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </details>

    <div class="fi-studio-preview-actions">
        <x-filament::button
            type="button"
            icon="heroicon-m-check"
            wire:click="save"
            wire:loading.attr="disabled"
            wire:target="save"
        >
            {{ __('Save changes') }}
        </x-filament::button>
        <p class="fi-studio-preview-save-note">
            {{ ($forUser ?? false) ? __('Saves your personal appearance.') : __('Saves the default appearance for everyone.') }}
        </p>
    </div>
</div>

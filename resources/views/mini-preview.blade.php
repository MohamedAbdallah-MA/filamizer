@php
    $statePath = $getStatePath();
    $isRtl = __('filament-panels::layout.direction') === 'rtl';
@endphp

{{-- A small dashboard drawn with the theme being edited (light or dark), whatever mode the page is in. --}}
{{-- No double quotes inside x-data: they would end the attribute. --}}
<div
    x-data="{
        state: {},
        init() {
            this.state = $wire.get('{{ $statePath }}') ?? {}
            this.$watch('$wire.{{ $statePath }}', (value) => this.state = value ?? {})
        },
        get dark() { return this.state.edit_mode === 'dark' },
        pick(key) { return this.state[(this.dark ? 'dark_' : '') + key] },
        hex(value, fallback) { return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(value ?? '') ? value : fallback },
        get skin() { return this.state.skin ?? 'classic' },
        get primary() { return this.hex(this.pick('primary'), '#B08C50') },
        get background() { return this.hex(this.pick('background'), this.dark ? '#0C0C0C' : '#FAFAF9') },
        get surface() { return this.hex(this.pick('surface'), this.dark ? '#1A1612' : '#FFFFFF') },
        get input() { return this.hex(this.pick('input'), this.surface) },
        get border() { return this.hex(this.pick('border'), this.dark ? '#342F29' : '#E8E5DF') },
        get text() { return this.dark ? 'rgb(255 255 255 / 0.9)' : 'rgb(0 0 0 / 0.82)' },
        get muted() { return this.dark ? 'rgb(255 255 255 / 0.35)' : 'rgb(0 0 0 / 0.18)' },
        get glassAlpha() { return Number(this.pick('glass_opacity') ?? 45) },
        get glow() { return Number(this.pick('glow_intensity') ?? 60) },
        canvasStyle() {
            let image = 'none'
            const strength = Number(this.pick('backdrop_intensity') ?? 100) / 100 * (this.dark ? 1.35 : 1)
            const mix = (color, share) => `color-mix(in oklab, ${color} ${Math.min(100, share * strength)}%, transparent)`
            const backdrop = this.state.backdrop ?? 'none'

            if (backdrop === 'gradient') image = `linear-gradient(135deg, ${mix(this.primary, 34)}, ${mix('#378ADD', 18)} 50%, ${mix('#1D9E75', 24)})`
            if (backdrop === 'mesh' || backdrop === 'aurora' || backdrop === 'image') image = `radial-gradient(at 12% 8%, ${mix(this.primary, 55)} 0, transparent 55%), radial-gradient(at 90% 15%, ${mix('#378ADD', 45)} 0, transparent 50%), radial-gradient(at 75% 95%, ${mix('#1D9E75', 40)} 0, transparent 55%)`

            return { backgroundColor: this.background, backgroundImage: image }
        },
        panelStyle() {
            const style = { backgroundColor: this.surface, color: this.text }

            if (this.skin === 'glass') {
                style.backgroundColor = `color-mix(in oklab, ${this.surface} ${this.glassAlpha}%, transparent)`
                style.backdropFilter = `blur(${this.state.glass_blur ?? 16}px) saturate(180%)`
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
                color: this.primary,
                boxShadow: this.skin === 'neon' ? `inset 0 0 0 1px color-mix(in oklab, ${this.primary} 50%, transparent)` : 'none',
            }
        },
    }"
    class="fi-studio-mini"
>
    <div class="fi-studio-mini-head">
        <span class="fi-studio-nav-group-title">{{ __('Preview') }}</span>
        <span class="fi-studio-profile-meta" x-text="dark ? @js(__('Dark theme')) : @js(__('Light theme'))"></span>
    </div>

    <div class="fi-studio-mini-canvas" x-bind:style="canvasStyle()" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
        <div class="fi-studio-mini-topbar" x-bind:style="panelStyle()">
            <span class="fi-studio-mini-logo" x-bind:style="{ color: text }">DEAL ART</span>
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

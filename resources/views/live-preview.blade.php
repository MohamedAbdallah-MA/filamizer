@php
    $statePath = $getStatePath();
    $lightImage = \DealArt\FilamentStudio\Support\StudioTheme::backdropImageUrl();
    $darkImage = \DealArt\FilamentStudio\Support\StudioTheme::backdropImageUrl(dark: true);

    $previewConfig = [
        'spacing' => collect(config('filament-studio.densities'))->map(fn ($d) => $d['spacing'])->all(),
        'radii' => collect(config('filament-studio.radii'))->map(fn ($r) => collect($r)->only(['sm', 'md', 'lg', 'xl'])->all())->all(),
        'fontSizes' => collect(config('filament-studio.font_sizes'))->map(fn ($s) => $s['size'])->all(),
        'semantic' => \DealArt\FilamentStudio\Support\StudioTheme::SEMANTIC_COLORS,
    ];

    $messages = [
        'lowContrast' => __('Text on cards may be hard to read (contrast :ratio:1, aim for at least 4.5:1).'),
        'lowContrastDark' => __('Text on dark cards may be hard to read (contrast :ratio:1, aim for at least 4.5:1).'),
        'neonLight' => __('Neon is designed for dark mode. Consider "Dark only" or a dark default mode.'),
        'glassPlain' => __('Glass looks flat over a plain color. Try the Aurora, mesh, gradient, or image backdrop.'),
    ];
@endphp

<div
    x-data="{
        config: @js($previewConfig),
        messages: @js($messages),
        warnings: [],
        images: { light: @js($lightImage ? \DealArt\FilamentStudio\Support\StudioTheme::cssUrl($lightImage) : 'none'), dark: @js($darkImage ? \DealArt\FilamentStudio\Support\StudioTheme::cssUrl($darkImage) : 'none') },
        motionReduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        hexPattern: /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/,
        // Share of the picked color per shade; light shades mix with white, dark ones with black.
        mixes: { 50: [10, 'white'], 100: [20, 'white'], 200: [35, 'white'], 300: [55, 'white'], 400: [78, 'white'], 500: [100, 'white'], 600: [85, 'black'], 700: [70, 'black'], 800: [56, 'black'], 900: [44, 'black'], 950: [30, 'black'] },

        init() {
            this.$watch('$wire.{{ $statePath }}', (state) => this.apply(state))

            this.modeObserver = new MutationObserver(() => this.apply(this.$wire.get('{{ $statePath }}')))
            this.modeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })

            this.warnings = this.checkWarnings(this.$wire.get('{{ $statePath }}'))
        },

        destroy() {
            this.modeObserver?.disconnect()
        },

        apply(state) {
            if (! state) return

            const html = document.documentElement
            const root = html.style
            const isDark = html.classList.contains('dark')
            const prefix = isDark ? 'dark_' : ''

            this.setColor(root, '--studio-text-color', state[prefix + 'text_color'])
            this.setColor(root, '--studio-muted-text-color', state[prefix + 'muted_text_color'])

            for (const key of ['background', 'surface', 'input', 'border']) {
                this.setColor(root, `--studio-${key}`, state[prefix + key])
            }

            for (const name of this.config.semantic) {
                const color = state[prefix + name]

                if (! this.hexPattern.test(color ?? '')) continue

                for (const [shade, [share, base]] of Object.entries(this.mixes)) {
                    root.setProperty(`--${name}-${shade}`, share === 100 ? color : `color-mix(in oklab, ${color} ${share}%, ${base})`)
                }
            }

            for (const [shade, hex] of Object.entries(state[prefix + 'gray_palette'] ?? {})) {
                this.setColor(root, `--gray-${shade}`, hex)
            }

            if (this.config.spacing[state.density]) root.setProperty('--spacing', this.config.spacing[state.density])
            if (this.config.fontSizes[state.font_size]) root.setProperty('--studio-font-size', this.config.fontSizes[state.font_size])

            for (const [size, value] of Object.entries(this.config.radii[state.radius] ?? {})) {
                root.setProperty(`--radius-${size}`, value)
            }

            const isRtl = html.dir === 'rtl'
            const fonts = [...new Set(isRtl ? [state.font_arabic, state.font_latin] : [state.font_latin, state.font_arabic])].filter(Boolean)
            // Pages without font fields, like My appearance, keep the current fonts.
            if (fonts.length) {
                fonts.forEach((font) => this.loadFont(font))
                root.setProperty('--studio-font-family', [...fonts.map((font) => `'${font}'`), 'ui-sans-serif', 'system-ui', 'sans-serif'].join(', '))
            }

            // Skins. A collapsible sidebar is a server-side panel option, so it only changes after saving.
            if (state[prefix + 'skin']) html.dataset.studioSkin = state[prefix + 'skin']
            if (state.sidebar_style && state.sidebar_style !== 'collapsible') html.dataset.studioSidebar = state.sidebar_style

            // An image backdrop can only be previewed once an image has been saved.
            const image = this.images[isDark ? 'dark' : 'light']
            root.setProperty('--studio-backdrop-image', image)
            const backdrop = state[prefix + 'backdrop']
            if (backdrop) html.dataset.studioBackdrop = (backdrop === 'image' && image === 'none') ? 'none' : backdrop
            html.dataset.studioMotion = state[prefix + 'aurora_animated'] === false || state[prefix + 'aurora_animated'] === 0 ? 'off' : 'on'

            for (const index of [1, 2, 3]) {
                this.setColor(root, `--studio-backdrop-color-${index}`, state[prefix + 'backdrop_color_' + index])
            }

            if (state[prefix + 'backdrop_angle'] != null) root.setProperty('--studio-backdrop-angle', `${Number(state[prefix + 'backdrop_angle'])}deg`)
            if (state[prefix + 'aurora_speed'] != null) root.setProperty('--studio-aurora-duration', `${Number(state[prefix + 'aurora_speed'])}s`)

            if (state[prefix + 'glass_blur'] != null) root.setProperty('--studio-blur', `${state[prefix + 'glass_blur']}px`)
            if (state[prefix + 'glass_opacity'] != null) root.setProperty('--studio-glass-alpha', `${state[prefix + 'glass_opacity']}%`)
            if (state[prefix + 'glow_intensity'] != null) root.setProperty('--studio-glow', `${state[prefix + 'glow_intensity']}%`)
            if (state[prefix + 'backdrop_intensity'] != null) root.setProperty('--studio-backdrop-intensity', state[prefix + 'backdrop_intensity'] / 100)
            if (state[prefix + 'glass_refraction'] != null) document.querySelector('[data-studio-lens]')?.setAttribute('scale', state[prefix + 'glass_refraction'])

            this.warnings = this.checkWarnings(state)
        },

        checkWarnings(state) {
            if (! state) return []

            const warnings = []
            const glassAlpha = state.skin === 'glass' ? (state.glass_opacity ?? 45) / 100 : 1
            const darkGlassAlpha = state.dark_skin === 'glass' ? (state.dark_glass_opacity ?? 40) / 100 : 1
            const lightText = state.text_color ?? state.gray_palette?.[950] ?? '#0C0C0C'

            const lightRatio = this.contrast(lightText, this.blend(state.surface, state.background, glassAlpha))
            if (lightRatio && lightRatio < 4.5 && state.dark_mode !== 'dark') {
                warnings.push(this.messages.lowContrast.replace(':ratio', lightRatio.toFixed(1)))
            }

            const darkRatio = this.contrast(state.dark_text_color ?? '#FFFFFF', this.blend(state.dark_surface, state.dark_background, darkGlassAlpha))
            if (darkRatio && darkRatio < 4.5 && state.dark_mode !== 'light') {
                warnings.push(this.messages.lowContrastDark.replace(':ratio', darkRatio.toFixed(1)))
            }

            if (state.skin === 'neon' && (state.dark_mode === 'light' || (state.dark_mode === 'toggle' && state.default_theme_mode === 'light'))) {
                warnings.push(this.messages.neonLight)
            }

            const editPrefix = state.edit_mode === 'dark' ? 'dark_' : ''
            if (state[editPrefix + 'skin'] === 'glass' && state[editPrefix + 'backdrop'] === 'none') {
                warnings.push(this.messages.glassPlain)
            }

            return warnings
        },

        rgb(hex) {
            if (! this.hexPattern.test(hex ?? '')) return null
            let h = hex.slice(1)
            if (h.length === 3) h = [...h].map((c) => c + c).join('')

            return [0, 2, 4].map((i) => parseInt(h.slice(i, i + 2), 16))
        },

        // Color of a translucent surface over the page background.
        blend(top, bottom, alpha) {
            const a = this.rgb(top), b = this.rgb(bottom)
            if (! a || ! b) return top

            return '#' + a.map((c, i) => Math.round(c * alpha + b[i] * (1 - alpha)).toString(16).padStart(2, '0')).join('')
        },

        luminance(hex) {
            const channels = this.rgb(hex)
            if (! channels) return null

            const [r, g, b] = channels.map((c) => {
                c /= 255
                return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4
            })

            return 0.2126 * r + 0.7152 * g + 0.0722 * b
        },

        contrast(a, b) {
            const la = this.luminance(a), lb = this.luminance(b)
            if (la === null || lb === null) return null

            return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05)
        },

        setColor(root, property, value) {
            if (this.hexPattern.test(value ?? '')) root.setProperty(property, value)
        },

        loadFont(font) {
            if (document.querySelector(`link[data-studio-font='${font}']`)) return

            const link = document.createElement('link')
            link.rel = 'stylesheet'
            link.dataset.studioFont = font
            link.href = `https://fonts.googleapis.com/css2?family=${font.replaceAll(' ', '+')}:wght@300;400;500;600;700&display=swap`
            document.head.appendChild(link)
        },
    }"
    class="fi-studio-preview"
>
    <div class="fi-studio-notice">
        <x-filament::icon icon="heroicon-o-eye" class="fi-studio-icon" />
        <span>
            {{ $this instanceof \DealArt\FilamentStudio\Filament\Pages\MyAppearance
                ? __('Changes preview live on this page. Save to keep them for you.')
                : __('Changes preview live on this page. Save to apply them for everyone.') }}
        </span>
    </div>

    <div class="fi-studio-notice" x-show="motionReduced && ($wire.{{ $statePath }}.edit_mode === 'dark' ? $wire.{{ $statePath }}.dark_backdrop : $wire.{{ $statePath }}.backdrop) === 'aurora'" x-cloak>
        {{ __('Animation is paused because your device requests reduced motion.') }}
    </div>

    {{-- Keep changing diagnostics out of the form's flow: a contrast threshold can
         otherwise move the color picker underneath the pointer during a drag. --}}
    <details class="fi-studio-checks"
        x-bind:data-has-warnings="warnings.length > 0 ? 'true' : 'false'"
        x-on:click.outside="$el.open = false"
        x-on:keydown.escape.prevent.stop="$el.open = false">
        <summary>
            <span>{{ __('Preview checks') }}</span>
            <span class="fi-studio-checks-count" x-text="warnings.length" aria-live="polite" aria-atomic="true"></span>
        </summary>
        <div class="fi-studio-checks-panel">
            <p class="fi-studio-notice" x-show="! warnings.length">{{ __('No preview warnings.') }}</p>
            <template x-for="(warning, index) in warnings" :key="index">
                <div class="fi-studio-warning" data-studio-warning>
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="fi-studio-icon" />
                    <span x-text="warning"></span>
                </div>
            </template>
        </div>
    </details>
</div>

@php
    $statePath = $getStatePath();

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
        hexPattern: /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/,
        // Share of the picked color per shade; light shades mix with white, dark ones with black.
        mixes: { 50: [10, 'white'], 100: [20, 'white'], 200: [35, 'white'], 300: [55, 'white'], 400: [78, 'white'], 500: [100, 'white'], 600: [85, 'black'], 700: [70, 'black'], 800: [56, 'black'], 900: [44, 'black'], 950: [30, 'black'] },

        init() {
            this.$watch('$wire.{{ $statePath }}', (state) => this.apply(state))

            new MutationObserver(() => this.apply(this.$wire.get('{{ $statePath }}')))
                .observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })

            this.warnings = this.checkWarnings(this.$wire.get('{{ $statePath }}'))
        },

        apply(state) {
            if (! state) return

            const html = document.documentElement
            const root = html.style
            const isDark = html.classList.contains('dark')
            const prefix = isDark ? 'dark_' : ''

            for (const key of ['background', 'surface', 'input', 'border']) {
                this.setColor(root, `--studio-${key}`, state[prefix + key])
            }

            for (const name of this.config.semantic) {
                if (! this.hexPattern.test(state[name] ?? '')) continue

                for (const [shade, [share, base]] of Object.entries(this.mixes)) {
                    root.setProperty(`--${name}-${shade}`, share === 100 ? state[name] : `color-mix(in oklab, ${state[name]} ${share}%, ${base})`)
                }
            }

            for (const [shade, hex] of Object.entries(state.gray_palette ?? {})) {
                this.setColor(root, `--gray-${shade}`, hex)
            }

            if (this.config.spacing[state.density]) root.setProperty('--spacing', this.config.spacing[state.density])
            if (this.config.fontSizes[state.font_size]) root.setProperty('--studio-font-size', this.config.fontSizes[state.font_size])

            for (const [size, value] of Object.entries(this.config.radii[state.radius] ?? {})) {
                root.setProperty(`--radius-${size}`, value)
            }

            const isRtl = html.dir === 'rtl'
            const fonts = [...new Set(isRtl ? [state.font_arabic, state.font_latin] : [state.font_latin, state.font_arabic])].filter(Boolean)
            fonts.forEach((font) => this.loadFont(font))
            root.setProperty('--studio-font-family', [...fonts.map((font) => `'${font}'`), 'ui-sans-serif', 'system-ui', 'sans-serif'].join(', '))

            // Skins. A collapsible sidebar is a server-side panel option, so it only changes after saving.
            if (state.skin) html.dataset.studioSkin = state.skin
            if (state.sidebar_style && state.sidebar_style !== 'collapsible') html.dataset.studioSidebar = state.sidebar_style

            // An image backdrop can only be previewed once an image has been saved.
            const hasSavedImage = getComputedStyle(html).getPropertyValue('--studio-backdrop-image').trim() !== ''
            if (state.backdrop) html.dataset.studioBackdrop = (state.backdrop === 'image' && ! hasSavedImage) ? 'none' : state.backdrop

            if (state.glass_blur != null) root.setProperty('--studio-blur', `${state.glass_blur}px`)
            if (state.glass_opacity != null) root.setProperty('--studio-glass-alpha', `${state.glass_opacity}%`)
            if (state.glow_intensity != null) root.setProperty('--studio-glow', `${state.glow_intensity}%`)
            if (state.backdrop_intensity != null) root.setProperty('--studio-backdrop-intensity', state.backdrop_intensity / 100)
            if (state.glass_refraction != null) document.querySelector('[data-studio-lens]')?.setAttribute('scale', state.glass_refraction)

            this.warnings = this.checkWarnings(state)
        },

        checkWarnings(state) {
            if (! state) return []

            const warnings = []
            const glassAlpha = state.skin === 'glass' ? (state.glass_opacity ?? 70) / 100 : 1
            const lightText = state.gray_palette?.[950] ?? '#0C0C0C'

            const lightRatio = this.contrast(lightText, this.blend(state.surface, state.background, glassAlpha))
            if (lightRatio && lightRatio < 4.5 && state.dark_mode !== 'dark') {
                warnings.push(this.messages.lowContrast.replace(':ratio', lightRatio.toFixed(1)))
            }

            const darkRatio = this.contrast('#FFFFFF', this.blend(state.dark_surface, state.dark_background, glassAlpha))
            if (darkRatio && darkRatio < 4.5 && state.dark_mode !== 'light') {
                warnings.push(this.messages.lowContrastDark.replace(':ratio', darkRatio.toFixed(1)))
            }

            if (state.skin === 'neon' && (state.dark_mode === 'light' || (state.dark_mode === 'toggle' && state.default_theme_mode === 'light'))) {
                warnings.push(this.messages.neonLight)
            }

            if (state.skin === 'glass' && state.backdrop === 'none') {
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
        <span>{{ __('Changes preview live on this page. Save to apply them for everyone.') }}</span>
    </div>

    <template x-for="warning in warnings" :key="warning">
        <div class="fi-studio-warning" data-studio-warning>
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="fi-studio-icon" />
            <span x-text="warning"></span>
        </div>
    </template>
</div>

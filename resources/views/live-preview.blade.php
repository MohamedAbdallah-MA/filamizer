@php
    $statePath = $getStatePath();

    $previewConfig = [
        'spacing' => collect(config('filament-studio.densities'))->map(fn ($d) => $d['spacing'])->all(),
        'radii' => collect(config('filament-studio.radii'))->map(fn ($r) => collect($r)->only(['sm', 'md', 'lg', 'xl'])->all())->all(),
        'fontSizes' => collect(config('filament-studio.font_sizes'))->map(fn ($s) => $s['size'])->all(),
        'semantic' => \DealArt\FilamentStudio\Support\StudioTheme::SEMANTIC_COLORS,
    ];
@endphp

<div
    x-data="{
        config: @js($previewConfig),
        hexPattern: /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/,
        // Share of the picked color per shade; light shades mix with white, dark ones with black.
        mixes: { 50: [10, 'white'], 100: [20, 'white'], 200: [35, 'white'], 300: [55, 'white'], 400: [78, 'white'], 500: [100, 'white'], 600: [85, 'black'], 700: [70, 'black'], 800: [56, 'black'], 900: [44, 'black'], 950: [30, 'black'] },

        init() {
            this.$watch('$wire.{{ $statePath }}', (state) => this.apply(state))

            new MutationObserver(() => this.apply(this.$wire.get('{{ $statePath }}')))
                .observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
        },

        apply(state) {
            if (! state) return

            const root = document.documentElement.style
            const isDark = document.documentElement.classList.contains('dark')
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

            const isRtl = document.documentElement.dir === 'rtl'
            const fonts = [...new Set(isRtl ? [state.font_arabic, state.font_latin] : [state.font_latin, state.font_arabic])].filter(Boolean)
            fonts.forEach((font) => this.loadFont(font))
            root.setProperty('--studio-font-family', [...fonts.map((font) => `'${font}'`), 'ui-sans-serif', 'system-ui', 'sans-serif'].join(', '))
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
    class="flex items-center gap-x-2 text-sm text-gray-500 dark:text-gray-400"
>
    <x-filament::icon icon="heroicon-o-eye" class="h-5 w-5" />
    <span>{{ __('Changes preview live on this page. Save to apply them for everyone.') }}</span>
</div>

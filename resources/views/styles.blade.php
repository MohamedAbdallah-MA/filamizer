@php
    use DealArt\FilamentStudio\Support\StudioTheme;

    $light = StudioTheme::surfaces();
    $dark = StudioTheme::surfaces(dark: true);
    $radii = StudioTheme::radii();
    $secondaryFont = StudioTheme::secondaryFontFor();
    $backdropImage = StudioTheme::backdropImageUrl();
    $darkBackdropImage = StudioTheme::backdropImageUrl(dark: true);
    $readabilityState = [];
    foreach (['', 'dark_'] as $prefix) {
        foreach (['skin', 'glass_auto_readability', 'text_color', 'muted_text_color', 'background', 'surface', 'input', 'glass_opacity', 'backdrop_intensity'] as $key) {
            $readabilityState[$prefix.$key] = StudioTheme::value($prefix.$key);
        }
        $readabilityState[$prefix.'backdrop'] = StudioTheme::backdrop(dark: $prefix !== '');
    }
@endphp

@include('filament-studio::readability')

{{-- Set before first paint so the skin never flashes. --}}
<script>
    (() => {
        const html = document.documentElement;
        const readabilityState = @js($readabilityState);
        const looks = {
            light: { skin: @js(StudioTheme::skin()), backdrop: @js(StudioTheme::backdrop()), motion: @js(StudioTheme::isAuroraAnimated() ? 'on' : 'off'), refraction: {{ StudioTheme::range('glass_refraction') }} },
            dark: { skin: @js(StudioTheme::skin(dark: true)), backdrop: @js(StudioTheme::backdrop(dark: true)), motion: @js(StudioTheme::isAuroraAnimated(dark: true) ? 'on' : 'off'), refraction: {{ StudioTheme::range('dark_glass_refraction') }} },
        };
        const applyMode = () => {
            const look = looks[html.classList.contains('dark') ? 'dark' : 'light'];
            html.dataset.studioSkin = look.skin;
            html.dataset.studioBackdrop = look.backdrop;
            html.dataset.studioMotion = look.motion;
            document.querySelector('[data-studio-lens]')?.setAttribute('scale', look.refraction);
            window.filamentStudioReadability.apply(readabilityState, html.classList.contains('dark'));
        };
        html.dataset.studioSidebar = @js(StudioTheme::sidebarStyle());
        applyMode();
        new MutationObserver(applyMode).observe(html, { attributes: true, attributeFilter: ['class'] });
        document.addEventListener('DOMContentLoaded', applyMode, { once: true });
    })();
</script>

@if ($secondaryFont !== StudioTheme::fontFor())
    <link href="{{ StudioTheme::googleFontsUrl($secondaryFont) }}" rel="stylesheet" data-studio-font="{{ $secondaryFont }}" />
@endif

<style id="filament-studio-styles">
    :root {
        --studio-text-color: {{ StudioTheme::color('text_color') }};
        --studio-muted-text-color: {{ StudioTheme::color('muted_text_color') }};
        --studio-background: {{ $light['background'] }};
        --studio-surface: {{ $light['surface'] }};
        --studio-input: {{ $light['input'] }};
        --studio-border: {{ $light['border'] }};
        --studio-font-family: {!! StudioTheme::fontStack() !!};
        --studio-font-size: {{ StudioTheme::rootFontSize() }};
        --spacing: {{ StudioTheme::spacing() }};
        --radius-sm: {{ $radii['sm'] }};
        --radius-md: {{ $radii['md'] }};
        --radius-lg: {{ $radii['lg'] }};
        --radius-xl: {{ $radii['xl'] }};
        --studio-blur: {{ StudioTheme::range('glass_blur') }}px;
        --studio-glass-alpha: {{ StudioTheme::range('glass_opacity') }}%;
        --studio-glow: {{ StudioTheme::range('glow_intensity') }}%;
        --studio-backdrop-intensity: {{ StudioTheme::range('backdrop_intensity') / 100 }};
        --studio-backdrop-angle: {{ StudioTheme::range('backdrop_angle') }}deg;
        --studio-aurora-duration: {{ StudioTheme::range('aurora_speed') }}s;
        @foreach ([1, 2, 3] as $index)
            --studio-backdrop-color-{{ $index }}: {{ StudioTheme::color('backdrop_color_'.$index) }};
        @endforeach
        @if ($backdropImage)
            --studio-backdrop-image: {!! StudioTheme::cssUrl($backdropImage) !!};
        @endif
    }

    :root.dark {
        --studio-text-color: {{ StudioTheme::color('dark_text_color') }};
        --studio-muted-text-color: {{ StudioTheme::color('dark_muted_text_color') }};
        --studio-background: {{ $dark['background'] }};
        --studio-surface: {{ $dark['surface'] }};
        --studio-input: {{ $dark['input'] }};
        --studio-border: {{ $dark['border'] }};
        --studio-blur: {{ StudioTheme::range('dark_glass_blur') }}px;
        --studio-glass-alpha: {{ StudioTheme::range('dark_glass_opacity') }}%;
        --studio-glow: {{ StudioTheme::range('dark_glow_intensity') }}%;
        --studio-backdrop-intensity: {{ StudioTheme::range('dark_backdrop_intensity') / 100 }};
        --studio-backdrop-angle: {{ StudioTheme::range('dark_backdrop_angle') }}deg;
        --studio-aurora-duration: {{ StudioTheme::range('dark_aurora_speed') }}s;
        @foreach ([1, 2, 3] as $index)
            --studio-backdrop-color-{{ $index }}: {{ StudioTheme::color('dark_backdrop_color_'.$index) }};
        @endforeach
        --studio-backdrop-image: {!! $darkBackdropImage ? StudioTheme::cssUrl($darkBackdropImage) : 'none' !!};
        @foreach (StudioTheme::grayPalette(dark: true) as $shade => $value)
            --gray-{{ $shade }}: {{ $value }};
        @endforeach
        @foreach (StudioTheme::SEMANTIC_COLORS as $name)
            @foreach (StudioTheme::darkColorPalette($name) as $shade => $value)
                --{{ $name }}-{{ $shade }}: {{ $value }};
            @endforeach
        @endforeach
    }

    html {
        font-size: var(--studio-font-size);
    }

    html,
    body,
    .fi-body {
        font-family: var(--studio-font-family);
    }

    /* Filament draws card/input borders as rings, so --tw-ring-color is the border. */
    .fi-body {
        background-color: var(--studio-background);
    }

    .fi-section:not(.fi-section-not-contained):not(.fi-aside):not(.fi-secondary),
    .fi-ta-ctn,
    .fi-wi-stats-overview-stat,
    .fi-modal-window,
    .fi-simple-main,
    .fi-dropdown-panel {
        background-color: var(--studio-surface);
        --tw-ring-color: var(--studio-border);
    }

    .fi-input-wrp:not(.fi-disabled) {
        background-color: var(--studio-input);
    }

    /* Leave focus and validation rings to Filament. */
    .fi-input-wrp:not(.fi-disabled):not(.fi-invalid):not(:focus-within) {
        --tw-ring-color: var(--studio-border);
    }
</style>

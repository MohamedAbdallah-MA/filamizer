@php
    use DealArt\FilamentStudio\Support\StudioTheme;

    $light = StudioTheme::surfaces();
    $dark = StudioTheme::surfaces(dark: true);
    $radii = StudioTheme::radii();
    $secondaryFont = StudioTheme::secondaryFontFor();
@endphp

@if ($secondaryFont !== StudioTheme::fontFor())
    <link href="{{ StudioTheme::googleFontsUrl($secondaryFont) }}" rel="stylesheet" data-studio-font="{{ $secondaryFont }}" />
@endif

<style id="filament-studio-styles">
    :root {
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
    }

    :root.dark {
        --studio-background: {{ $dark['background'] }};
        --studio-surface: {{ $dark['surface'] }};
        --studio-input: {{ $dark['input'] }};
        --studio-border: {{ $dark['border'] }};
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

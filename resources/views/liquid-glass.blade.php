@php
    use FilamentCustomizer\Support\StudioTheme;

    /*
     * Displacement map for the lens: red drives horizontal shift, green vertical,
     * 50% is neutral. Edges push pixels inward and the center stays clear, so the
     * glass bends what is behind its rim like a real lens. It is stretched to each
     * element's box, so one map fits every surface.
     */
    $map = <<<'SVG'
    <svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100' preserveAspectRatio='none'>
        <defs>
            <linearGradient id='x'>
                <stop offset='0' stop-color='#ff0000'/>
                <stop offset='0.08' stop-color='#800000'/>
                <stop offset='0.92' stop-color='#800000'/>
                <stop offset='1' stop-color='#000000'/>
            </linearGradient>
            <linearGradient id='y' x2='0' y2='1'>
                <stop offset='0' stop-color='#00ff00'/>
                <stop offset='0.12' stop-color='#008000'/>
                <stop offset='0.88' stop-color='#008000'/>
                <stop offset='1' stop-color='#000000'/>
            </linearGradient>
        </defs>
        <rect width='100' height='100' fill='url(#x)'/>
        <rect width='100' height='100' fill='url(#y)' style='mix-blend-mode:screen'/>
    </svg>
    SVG;

    $mapUri = 'data:image/svg+xml,'.rawurlencode(preg_replace('/\s+/', ' ', trim($map)));
@endphp

<svg width="0" height="0" aria-hidden="true" focusable="false" style="position: absolute; pointer-events: none;">
    <filter id="studio-liquid-lens" x="0" y="0" width="100%" height="100%" color-interpolation-filters="sRGB">
        <feImage href="{{ $mapUri }}" preserveAspectRatio="none" result="map" />
        <feGaussianBlur in="map" stdDeviation="2" result="softMap" />
        <feDisplacementMap
            in="SourceGraphic"
            in2="softMap"
            scale="{{ StudioTheme::range('glass_refraction') }}"
            xChannelSelector="R"
            yChannelSelector="G"
            data-studio-lens
        />
    </filter>
</svg>

<script>
    (() => {
        const html = document.documentElement

        // SVG filters inside backdrop-filter only render in Chromium. Firefox accepts them
        // and draws nothing, and @supports can't tell, so check the engine instead.
        const brands = navigator.userAgentData?.brands ?? []
        const isChromium = brands.length
            ? brands.some(({ brand }) => brand === 'Chromium')
            : /Chrome\//.test(navigator.userAgent) && ! /Firefox\//.test(navigator.userAgent)

        if (isChromium && ! matchMedia('(prefers-reduced-transparency: reduce)').matches) {
            html.dataset.studioRefraction = 'on'
        }

        if (matchMedia('(prefers-reduced-motion: reduce)').matches) return

        // A specular highlight follows the pointer, like light catching the glass. It fades
        // in where the pointer enters a surface and fades out when the pointer leaves it.
        const surfaces = '.fi-topbar, .fi-sidebar, .fi-section, .fi-ta-ctn, .fi-wi-stats-overview-stat, .fi-modal-window, .fi-simple-main, .fi-dropdown-panel'
        let current = null
        let frame = null
        let lastEvent = null

        const moveLight = (surface, event) => {
            const box = surface.getBoundingClientRect()
            surface.style.setProperty('--studio-light-x', `${event.clientX - box.left}px`)
            surface.style.setProperty('--studio-light-y', `${event.clientY - box.top}px`)
        }

        const leave = () => {
            current?.style.setProperty('--studio-light-strength', '0%')
            current = null
        }

        const enter = (surface, event) => {
            // Place the light at the entry point with no position transition, so it
            // never slides over from where the pointer last left this surface.
            surface.dataset.studioLightSnap = ''
            moveLight(surface, event)

            // Reading the style commits the snapped position before transitions return.
            getComputedStyle(surface, '::after').getPropertyValue('--studio-light-x')

            delete surface.dataset.studioLightSnap
            surface.style.setProperty('--studio-light-strength', '100%')

            current = surface
        }

        const update = () => {
            frame = null

            if (html.dataset.studioSkin !== 'glass') return leave()

            const surface = lastEvent.target.closest?.(surfaces) ?? null

            if (surface !== current) {
                leave()

                if (surface) enter(surface, lastEvent)

                return
            }

            if (surface) moveLight(surface, lastEvent)
        }

        document.addEventListener('pointermove', (event) => {
            lastEvent = event
            frame ??= requestAnimationFrame(update)
        }, { passive: true })

        // Leaving the window, or a page update from Livewire, also turns the light off.
        document.documentElement.addEventListener('pointerleave', leave)
        document.addEventListener('livewire:navigated', leave)
    })()
</script>

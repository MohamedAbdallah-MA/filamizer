{{-- One resolver for saved pages, live editing and the miniature. Never mutate saved choices. --}}
<script>
    (() => {
        const validHex = /^#(?:[a-f\d]{3}|[a-f\d]{6})$/i;
        const hex = (value, fallback) => validHex.test(value ?? '') ? value : fallback;
        const rgb = (value) => {
            let digits = value.slice(1);
            if (digits.length === 3) digits = [...digits].map((digit) => digit + digit).join('');
            return [0, 2, 4].map((offset) => parseInt(digits.slice(offset, offset + 2), 16));
        };
        const toHex = (channels) => '#' + channels.map((channel) => Math.round(channel).toString(16).padStart(2, '0')).join('');
        const luminance = (channels) => channels.reduce((sum, channel, index) => {
            const value = channel / 255;
            return sum + [0.2126, 0.7152, 0.0722][index] * (value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4);
        }, 0);
        const ratio = (a, b) => (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);
        const minimumContrast = (color, bounds) => {
            const foreground = luminance(rgb(color));
            const low = luminance(bounds[0]), high = luminance(bounds[1]);
            // Checking only endpoints misses an unreadable middle of a gradient.
            if (foreground >= low && foreground <= high) return 1;
            return Math.min(ratio(foreground, low), ratio(foreground, high));
        };
        const blend = (tint, background, alpha) => tint.map((channel, index) => channel * alpha + background[index] * (1 - alpha));
        const unknownBackdrop = [[0, 0, 0], [255, 255, 255]];
        const boundsAt = (tint, alpha, backdrop) => [
            blend(tint, backdrop[0], alpha).map((channel, index) => Math.min(channel, tint[index])),
            blend(tint, backdrop[1], alpha).map((channel, index) => Math.max(channel, tint[index])),
        ];
        const protect = (tint, requestedAlpha, backdrop) => {
            const opaque = [tint, tint];
            const ink = minimumContrast('#000000', opaque) >= minimumContrast('#FFFFFF', opaque) ? '#000000' : '#FFFFFF';
            // Stronger backing leaves room for a secondary foreground rather than
            // forcing every label to the same black/white. Mid-gray may need opacity.
            const target = Math.min(7, minimumContrast(ink, opaque)) - 0.001;
            let low = requestedAlpha, high = 1;
            if (minimumContrast(ink, boundsAt(tint, low, backdrop)) < target) {
                for (let index = 0; index < 24; index++) {
                    const middle = (low + high) / 2;
                    if (minimumContrast(ink, boundsAt(tint, middle, backdrop)) >= target) high = middle;
                    else low = middle;
                }
                low = high;
            }
            const alpha = Math.ceil(low * 10000) / 10000;
            return { alpha, bounds: boundsAt(tint, alpha, backdrop), ink };
        };
        const readable = (chosen, material) => {
            if (minimumContrast(chosen, material.bounds) >= 4.5) return chosen;
            const start = rgb(chosen), end = rgb(material.ink);
            let low = 0, high = 1, result = material.ink;
            for (let index = 0; index < 24; index++) {
                const middle = (low + high) / 2;
                const candidate = toHex(blend(end, start, middle));
                if (minimumContrast(candidate, material.bounds) >= 4.5) {
                    result = candidate;
                    high = middle;
                } else low = middle;
            }
            return result;
        };
        const resolve = (state = {}, dark = false) => {
            const prefix = dark ? 'dark_' : '';
            const get = (key) => state[prefix + key];
            const surface = hex(get('surface'), dark ? '#111827' : '#FFFFFF');
            const input = hex(get('input'), surface);
            const background = hex(get('background'), dark ? '#030712' : '#F9FAFB');
            const text = hex(get('text_color'), dark ? '#FFFFFF' : '#030712');
            const muted = hex(get('muted_text_color'), dark ? '#9CA3AF' : '#6B7280');
            const opacityValue = Number(get('glass_opacity') ?? (dark ? 40 : 45));
            const opacity = Number.isFinite(opacityValue) ? Math.max(15, Math.min(90, opacityValue)) : 45;
            const isGlass = get('skin') === 'glass';
            const option = get('glass_auto_readability');
            const automatic = isGlass && ![false, 0, '0', 'false', 'off'].includes(option);
            const hasBackdrop = ['gradient', 'mesh', 'aurora', 'image'].includes(get('backdrop')) && Number(get('backdrop_intensity') ?? 100) > 0;
            const base = rgb(background);
            const pageBounds = hasBackdrop ? unknownBackdrop : [base, base];
            const rawSurfaceBounds = isGlass ? boundsAt(rgb(surface), opacity / 100, unknownBackdrop) : [rgb(surface), rgb(surface)];
            const rawInputBounds = isGlass ? boundsAt(rgb(input), (opacity - 5) / 100, unknownBackdrop) : [rgb(input), rgb(input)];
            if (!automatic) return {
                automatic, bodyText: text, bodyMuted: muted, surfaceText: text, surfaceMuted: muted,
                inputText: text, inputMuted: muted, glassOpacity: opacity, inputOpacity: opacity - 5,
                pageScrim: 'transparent', pageScrimAlpha: 0,
                bounds: { page: pageBounds, surface: rawSurfaceBounds, input: rawInputBounds },
            };
            const page = protect(base, 0, pageBounds);
            // Floating glass can overlap arbitrary page content and images. Bound
            // it against the whole RGB range, not just the selected base color.
            const card = protect(rgb(surface), opacity / 100, unknownBackdrop);
            const field = protect(rgb(input), (opacity - 5) / 100, unknownBackdrop);
            return {
                automatic, bodyText: readable(text, page), bodyMuted: readable(muted, page),
                surfaceText: readable(text, card), surfaceMuted: readable(muted, card),
                inputText: readable(text, field), inputMuted: readable(muted, field),
                surfaceTone: card.ink === '#000000' ? 'light' : 'dark',
                glassOpacity: card.alpha * 100, inputOpacity: field.alpha * 100,
                pageScrim: `rgb(${base.join(' ')} / ${page.alpha})`, pageScrimAlpha: page.alpha,
                bounds: { page: page.bounds, surface: card.bounds, input: field.bounds },
            };
        };
        const apply = (state, dark) => {
            const result = resolve(state, dark);
            const html = document.documentElement;
            html.dataset.studioGlassReadability = result.automatic ? 'auto' : 'manual';
            html.dataset.studioGlassTone = result.surfaceTone ?? '';
            for (const [name, value] of Object.entries({
                '--studio-text-color': result.bodyText, '--studio-muted-text-color': result.bodyMuted,
                '--studio-glass-text-color': result.surfaceText, '--studio-glass-muted-text-color': result.surfaceMuted,
                '--studio-glass-input-text-color': result.inputText, '--studio-glass-input-muted-text-color': result.inputMuted,
                '--studio-glass-alpha': `${result.glassOpacity}%`, '--studio-glass-input-alpha': `${result.inputOpacity}%`,
                '--studio-page-scrim': result.pageScrim,
            })) html.style.setProperty(name, value);
            return result;
        };
        window.filamentStudioReadability = { resolve, apply, minimumContrast };
    })();
</script>

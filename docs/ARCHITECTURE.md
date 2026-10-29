# Filament Studio — Architecture and Handover

> Written as a complete handover for a developer, or an AI assistant, picking this
> project up on another machine with no prior context. Read it top to bottom once;
> after that, sections 5–9 are the reference.
>
> Last updated: 2026-10-22 · Package version `0.1.0` · Branch `feature/filament-studio`

---

## 1. What this is, in one paragraph

**Filament Studio** (`deal-art/filament-studio`, namespace `DealArt\FilamentStudio`) is a
Filament 5 panel plugin that lets people restyle a Filament admin dashboard **at runtime,
without rebuilding CSS**: colors, Arabic and English fonts, text size, density, corner
radius, light/dark mode, five visual **skins** (Classic, Liquid Glass, Neon, Soft,
Minimal), animated **backdrops**, a drag-and-drop **sidebar builder**, saved **profiles**,
and a **per-user "My appearance"** page on top of an admin-defined default. It is
developed inside the Deal Art dashboard (`D:\programs\laragon\www\deal-art`) as a local
path package, with the explicit goal of being **extracted and sold on the Filament plugin
marketplace** later. Arabic (RTL) is a first-class citizen.

## 2. Product owner context (read this before changing UI)

- Owner: Mohamed Abdallah (Deal Art / Quarizm). The dashboard is Arabic-first; every
  user-facing string must exist in Arabic (`resources/lang/ar.json`).
- He judges by **look and feel** and iterates visually. For any significant UI change,
  **show a mock first** (he explicitly asked for this), then build.
- He wants it **simple for normal users**: everyday choices visible, everything else
  behind "Show advanced settings". He has said he will request **more Liquid Glass
  tweaks later**.
- The plugin will be sold, so: no hard dependency on this app's classes inside the
  package, defensive coding, sane defaults, everything translatable.

## 3. Stack and environment

| Thing | Version / note |
|---|---|
| PHP | 8.4 (Herd `php` on Windows; **use PowerShell for `php`/`composer`** — the Git Bash shell has no `php`) |
| Laravel | 12 |
| Filament | 5 (Livewire 4, Alpine, Tailwind 4 compiled theme) |
| spatie/laravel-settings | 3.9 (storage for the admin defaults) |
| spatie/laravel-package-tools | 1.9x (service provider) |
| filament/spatie-laravel-settings-plugin | 5.7 (General Settings page base class) |
| Shield | bezhansalleh/filament-shield 4 (roles; super admin = `user_type SUPER_ADMIN` or role `super_admin`) |
| Local URL | `http://deal-art.localhost/admin` (Laragon). Seeded super admin is in `database/seeders/RolesAndUserSeeder.php` |
| Tests | PHPUnit, SQLite in-memory (`phpunit.xml`) |

Gotchas on this machine:
- Writing `composer.json` with PowerShell `Set-Content` adds a **UTF-8 BOM** and breaks Composer. Edit with the editor tool or `sed`.
- `public/js/ysfkaya/filament-phone-input/...js` shows as modified after every `filament:assets` / `filament:upgrade`. It is unrelated noise — don't commit it with Studio work.

## 4. History: phases and commits

All work is on branch **`feature/filament-studio`** (cut from `main` at `172e81d`). Nothing
has been pushed or merged yet.

| Commit | Phase | Summary |
|---|---|---|
| `e653dad` | **Phase 1 — base customizer** | Ported an old, never-merged stash (`dashboard_themes` config + GeneralSettings fields) into a standalone package. Spatie settings group `studio`, theme presets, semantic + surface colors, gray palette, **separate Arabic/English fonts by locale**, root font size, density (`--spacing`), radius (`--radius-*`), dark-mode policy, live preview. Editor = tab in General Settings, super admin only. |
| `79e3d89` | **Stage 2 — skins** | Pre-built skin stylesheet (Classic/Glass/Neon/Soft/Minimal), floating/collapsible sidebar, backdrops (gradient, mesh, **Aurora** animated, image) with intensity, **Liquid Glass** (layered frosted glass + specular rim + pointer highlight + SVG refraction, Chromium only), contrast warnings, login card glass, cache-busting CSS URL. |
| `fd5bada` | **Stage 3 — sidebar builder** | Drag-and-drop navigation editor tab (reorder/regroup/rename AR+EN/hide/icons via visual picker), applied through a custom `NavigationManager`. |
| *(uncommitted)* | **Redesign + Stage 4 (partial)** | Simple editor + advanced toggle, **per-theme (light/dark) colors**, **profiles**, **Reset to default**, **per-user overrides + "My appearance" page**, mini dashboard preview, save-time sanitizing. See §11 for exact status. |

Before phase 1 the user's unrelated WIP on `main` was stashed: **`stash@{0}` "wip before
filament-studio"** (AuthController, bootstrap/providers.php, composer files). It is
still there; restore it on `main` with `git stash pop` when appropriate.

## 5. Core architectural decisions (and why)

1. **Runtime theming through CSS custom properties, never a rebuild.**
   Filament 5's compiled theme already reads `var(--primary-500)`, `var(--spacing)`,
   `var(--radius-lg)`, etc. We override those variables (and our own `--studio-*` ones)
   in a `<style>` injected at `PanelsRenderHook::HEAD_END`. Changing a setting = new
   variable values on the next request. Live preview = Alpine writing the same variables
   inline on `<html>`.
2. **Skins are one pre-built CSS file scoped by attributes on `<html>`**
   (`data-studio-skin`, `data-studio-sidebar`, `data-studio-backdrop`,
   `data-studio-refraction`). An inline script in the head sets them before first paint
   (no flash). Buyers never compile anything. The file is registered with
   `FilamentAsset` and must be **published** (`php artisan filament:assets`).
3. **One resolver for every value: `StudioTheme`.** Nothing reads settings directly.
   Every getter validates against config and falls back to `config('filament-studio.defaults')`,
   wrapped in `rescue()` so artisan/migrations work before tables exist.
4. **Two layers of storage:**
   - **Admin defaults** = spatie settings class `StudioSettings` (group `studio`).
   - **Per-user overrides** = table `studio_user_preferences` (JSON, only the keys that
     differ from the admin default). Merged at read time by `StudioPreferences`.
   - **Profiles** = table `studio_profiles` (`user_id` null = shared by admin).
5. **Light and dark are independent.** Plain keys are the light theme; `dark_*` keys are
   the dark theme (`primary`/`dark_primary`, `background`/`dark_background`, glass
   opacity, glow, backdrop intensity…). The dark primary palette is generated server-side
   with `Color::generatePalette()` and emitted under `:root.dark`.
6. **Sidebar builder never touches permissions.** It post-processes the navigation
   Filament already built for the current user (visibility, policies, URLs, badges,
   active state stay Filament's). "Hidden" is cosmetic, not access control.
7. **The package must not know the host app.** Who may edit is a closure the host passes:
   `StudioPlugin::make()->canCustomize(fn () => auth()->user()?->isSuperAdmin())`.
   The admin editor is a reusable `Tab` + trait any spatie `SettingsPage` can host.
8. **Defense in depth on input.** Filament skips validation for **hidden** fields (and
   our editor deliberately keeps hidden fields via `dehydratedWhenHidden()`), so every
   write passes `StudioTheme::sanitizeValues()`, and every read validates again.

## 6. Package layout

```
packages/deal-art/filament-studio/
├─ composer.json                     version 0.1.0; provider auto-discovered
├─ config/filament-studio.php        presets, fonts, sizes, densities, radii, skins,
│                                     sidebar styles, backdrops, ranges, navigation,
│                                     user_keys, theme_keys, profiles, defaults
├─ database/settings/                spatie settings migrations (group "studio")
│   ├─ 2026_10_19_… create (phase 1 keys)
│   ├─ 2026_10_20_… skins            ├─ 2026_10_21_… glass_refraction
│   ├─ 2026_10_22_… backdrop_intensity
│   ├─ 2026_10_23_… navigation_layout
│   └─ 2026_10_24_… dark_* values + user_skins (dark_primary copies current primary)
├─ database/migrations/2026_10_24_… studio_user_preferences + studio_profiles tables
├─ resources/dist/filament-studio.css  ALL skin/editor CSS (source of truth)
├─ resources/lang/ar.json              every Arabic string
├─ resources/views/
│   ├─ styles.blade.php               HEAD_END: <html> data-attributes + CSS variables
│   ├─ liquid-glass.blade.php         BODY_END: SVG lens filter + pointer highlight JS
│   ├─ live-preview.blade.php         recolors the real page while editing + warnings
│   ├─ mini-preview.blade.php         small dashboard drawn with the edited theme
│   ├─ profiles.blade.php             profile cards (apply / delete)
│   ├─ navigation-editor.blade.php    sidebar builder (Alpine + SortableJS) + icon picker
│   └─ navigation-editor-item.blade.php one draggable item row
└─ src/
   ├─ FilamentStudioServiceProvider.php
   ├─ StudioPlugin.php                Filament plugin: colors, fonts, dark mode, hooks,
   │                                   My appearance page + user-menu item
   ├─ Settings/StudioSettings.php     spatie settings (admin defaults)
   ├─ Support/StudioTheme.php         THE resolver/validator (static API)
   ├─ Support/StudioPreferences.php   admin defaults + current user's overrides (scoped)
   ├─ Support/StudioCss.php           Css asset whose version includes file mtime
   ├─ Models/StudioUserPreference.php, StudioProfile.php
   ├─ Concerns/ManagesStudioAppearance.php    profiles + reset (page-agnostic)
   ├─ Concerns/InteractsWithStudioSettings.php admin-tab host trait (fill/save)
   ├─ Filament/StudioAppearanceEditor.php     the editor schema (simple + advanced)
   ├─ Filament/StudioAppearanceTab.php        admin tab wrapper
   ├─ Filament/StudioSidebarTab.php           sidebar builder tab
   ├─ Filament/Pages/MyAppearance.php         per-user page (/admin/my-appearance)
   ├─ Filament/Components/NavigationLayoutEditor.php  custom Field for the builder
   ├─ Navigation/StudioNavigation.php         layout catalog/merge/sanitize/apply
   ├─ Navigation/StudioNavigationManager.php  Filament NavigationManager subclass
   └─ Commands/InstallCommand.php     filament-studio:install (publish config + migrate)
```

Host-app touch points (outside the package):
- `composer.json`: path repository `packages/deal-art/filament-studio` (symlink) + `"deal-art/filament-studio": "^0.1"`.
- `app/Providers/Filament/AdminPanelProvider.php`: hard-coded `->font()` / `->colors()` removed; `StudioPlugin::make()->canCustomize(fn () => (bool) auth()->user()?->isSuperAdmin())` in `->plugins()`.
- `app/Filament/Pages/ManageGeneralSettings.php`: `use InteractsWithStudioSettings;` tabs `'studio_appearance' => StudioAppearanceTab::make()`, `'studio_sidebar' => StudioSidebarTab::make()`; `mutateFormDataBeforeFill` returns `$this->fillStudioData($data)`, `mutateFormDataBeforeSave` returns `$this->saveStudioData($data)`, `afterSave` calls `$this->reloadIfStudioSettingsChanged()`.
- `public/css/deal-art/filament-studio/filament-studio.css`: the **published copy** of the skin CSS (committed, like Filament's own assets).

## 7. Request lifecycle (how a page gets its look)

1. **Boot.** Provider registers `StudioSettings`, binds `StudioPreferences` (scoped),
   rebinds Filament's `NavigationManager` to `StudioNavigationManager` (scoped, in
   `packageBooted` so it wins over Filament's `register()` binding), registers the CSS
   asset, loads both migration folders.
2. **Panel registration.** `StudioPlugin::register()` gives the panel **closures** for
   colors, font, dark mode and collapsible sidebar (evaluated per request, after auth
   and locale middleware). `defaultThemeMode()` cannot take a closure, so it is read at
   registration (global value only — known limitation).
3. **Value resolution.** `StudioTheme::value($key)` → `StudioPreferences::value($key)`:
   the user's override if `$key ∈ config('filament-studio.user_keys')`, the skin is in
   the admin's `user_skins`, and user customization is enabled; else the admin setting;
   else the config default. The user row is loaded once per request.
4. **HEAD_END (`styles.blade.php`).** Inline script sets `data-studio-skin/sidebar/backdrop`
   on `<html>` (statements end with `;` — Blade's `@js` swallows the newline). Then a
   `<style id="filament-studio-styles">` with `:root { … }` (light values, fonts, size,
   spacing, radii, glass/glow/backdrop strengths, backdrop image URL) and
   `:root.dark { … }` (dark surfaces, dark strengths, **dark `--primary-*` palette**),
   plus base rules for `.fi-body`, sections, tables, stats, modals, dropdowns, inputs and
   the login card (`.fi-simple-main`).
5. **Skin CSS** (`filament-studio.css`, loaded by Filament's asset system with a
   `?v=<pkg version>.<mtime>` URL) applies skin rules matching the `<html>` attributes.
6. **BODY_END (`liquid-glass.blade.php`).** Hidden SVG `<filter id="studio-liquid-lens">`
   (displacement map generated as a data-URI SVG, `scale` = refraction setting) and a
   script that sets `data-studio-refraction="on"` **only in Chromium** (detected by
   engine, not `@supports`, because Firefox accepts `url()` in `backdrop-filter` and
   renders nothing), plus the pointer-following specular highlight.
7. **Sidebar.** Filament calls `NavigationManager::get()`; ours calls the parent (normal
   Filament build for this user) then `StudioNavigation::apply()` with the saved layout.

## 8. Data model

### 8.1 `StudioSettings` (spatie, group `studio`) — admin defaults

| Group | Keys |
|---|---|
| Identity | `preset` (?string) |
| Semantic colors | `primary` `success` `warning` `danger` `info` (hex) |
| Neutral | `gray_palette` (50…950 hex map) |
| Light surfaces | `background` `surface` `input` `border` |
| Text | `text_color` and `muted_text_color`, with independent `dark_` values; safe hex colors |
| Dark theme | `dark_primary` `dark_background` `dark_surface` `dark_input` `dark_border` |
| Typography | `font_arabic` `font_latin` `font_size` (sm/md/lg/xl → 93.75–112.5 % root size) |
| Layout | `density` (compact/comfortable/spacious → `--spacing`) `radius` (none…xl → `--radius-*`) |
| Dark mode policy | `dark_mode` (toggle/light/dark) `default_theme_mode` (system/light/dark) |
| Skins & backdrops | Light/dark `skin`, `backdrop` (none/gradient/mesh/aurora/image), `backdrop_image`; shared `sidebar_style` (classic/floating/collapsible) |
| Effect strengths | Light/dark `glass_blur`, `glass_refraction`, `glass_opacity`, `glow_intensity`, `backdrop_intensity` |
| Backdrop controls | Light/dark `backdrop_color_1/2/3`, `backdrop_angle` (0–360), `aurora_speed` (4–30 seconds), `aurora_animated` |
| Sidebar builder | `navigation_layout` (array, see §9.4) |
| Personal looks | `user_skins` (skins users may pick; `[]` disables My appearance) |

Ranges for sliders live in `config('filament-studio.ranges')`; `StudioTheme::range()` clamps.
**Adding a key = config default + `StudioSettings` property + a new settings migration**
(never edit an already-run migration; the first one lists its keys explicitly for that reason).

### 8.2 `studio_user_preferences`
`id, user_id (unique), data json, timestamps`. Only keys in `config('filament-studio.user_keys')`
whose value **differs** from the admin default are stored (`StudioPreferences::saveForUser`).
Saving values equal to the defaults deletes the row — that is how a user's "Reset" persists.

### 8.3 `studio_profiles`
`id, user_id (nullable, indexed), name, data json, timestamps`. `user_id = null` → shared
profile created from the admin tab, visible to everyone. Users see shared + their own and
can delete only their own. Admin profiles store all appearance keys except
`navigation_layout, user_skins, dark_mode, default_theme_mode`; user profiles store
`user_keys`. Applying a profile only fills the form; **nothing changes until Save**.
Limit: `profiles.max_per_user` (20); updating an existing name is allowed at the limit.
Profile mutations and resets check page authorization on the server, including Livewire calls.

## 9. Subsystems in detail

### 9.1 Skins (`resources/dist/filament-studio.css`)
- Target Filament 5 `.fi-*` classes: `.fi-body .fi-topbar .fi-sidebar .fi-section .fi-ta-ctn
  .fi-wi-stats-overview-stat .fi-modal-window .fi-dropdown-panel .fi-simple-main .fi-input-wrp
  .fi-btn .fi-sidebar-item .fi-badge`. Filament draws borders as **rings**
  (`--tw-ring-color`), not `border-color`.
- **Liquid Glass** layers (pseudo-elements, never on the element itself, because
  `backdrop-filter` on an element becomes the containing block for `position:fixed`
  children and breaks dropdowns):
  `::before` = tint + `blur() saturate(185%) brightness()`, `::after` = specular rim
  (inset top-left highlight), inner glow, and a pointer highlight using registered
  `@property` vars `--studio-light-x/-y/-strength` so it **fades in at the entry point
  and fades out on leave** (snap on enter via `data-studio-light-snap`).
  Refraction (`url(#studio-liquid-lens)`) only on floating surfaces (topbar, sidebar,
  modals, dropdowns) through `--studio-glass-lens`; cards reset it to `initial` (cost).
  Fallbacks: `@supports not (backdrop-filter)` and `prefers-reduced-transparency` → solid.
  Glass inputs and sections create stacking contexts: `:focus-within` raises both
  levels so select menus and color pickers stay above later fields/sections. Select
  option panels use a solid surface without refraction for readability. Color inputs
  explicitly use LTR direction so hex values keep their `#` prefix in Arabic.
  Unfocused glass inputs keep the inner white highlight but draw their outer ring
  using `--studio-border`, including native date inputs. Focus/invalid/disabled
  states retain Filament's own rings.
- **Neon**: glow from `--studio-glow` on cards, primary buttons, active nav item, focused inputs.
- **Soft** / **Minimal**: shadows vs hairlines. **Classic**: Filament + colors.
- **Backdrops**: gradient/mesh scale by `--studio-backdrop-strength`
  (= intensity × 1.35 in dark). Gradient uses two independent background colors and an
  angle; mesh/Aurora use three. Aurora = fixed blurred blobs on `.fi-body::before`,
  with configurable duration (default 14s), disabled by the motion toggle or
  `prefers-reduced-motion`. Image uses a separate layer so intensity changes its opacity.
- **Sidebar styles**: floating (≥64rem, margin + radius + surface), collapsible
  (`sidebarCollapsibleOnDesktop`, server-side → applies after save).
- Editor UI classes (`.fi-studio-*`) are also in this file. Do **not** rely on Tailwind
  utilities in package views — they may not exist in Filament's pre-built CSS.

### 9.2 The editor (`StudioAppearanceEditor`)
Shared by the admin tab (`forUser: false`) and My appearance (`forUser: true`); state path
`data.studio`. The current layout is an experiment on `codex/appearance-simplified-ux`.
- **Profiles stay above the editor** in their own section, with a prominent **Save as
  profile** header action. Cards name both Light and Dark looks and show their color
  swatches and skins. Applying a profile fills the editor; Save changes persists the
  active appearance. Shared/personal ownership and deletion rules are unchanged.
- **Make it yours** contains two independent UI-only choices: **Mode to edit**
  (`edit_mode`, Light/Dark) and **Editing tools** (`show_advanced`, Quick setup/Full
  customization). Both tool levels use the same fields and state. Quick setup reduces
  visible choices without replacing or resetting precise values.
- **Style and color**: visual skin choices and main color; administrators also get an
  optional **Starting palette** beside the main color. A starting palette initializes
  that mode's semantic, text, surface and neutral colors. Full customization reveals
  text color, secondary text color and card/sidebar tint in this same section; Glass
  additionally shows opacity/blur/refraction and an independent per-theme Automatic readability toggle (default on), and Neon shows glow. Text controls are
  available on My appearance and stored in profiles. Skin, backdrop, effects, palettes
  and colors have independent `dark_` keys. Users can retain an inherited global skin
  outside `user_skins`; it is removed from personal overrides rather than blocking Save.
- **Page background**: Quick setup exposes the solid base color, visual background
  choices, four optional backdrop palettes, Aurora motion, and admin image upload.
  Full customization reveals exact colors, gradient direction, animation duration and
  intensity beside those choices. Backdrop palettes affect only the edited mode's
  background colors, never semantic colors. Their selector is UI-only. Users can retain
  an inherited image backdrop but cannot upload images.
- **Reading and layout**: text size in Quick setup; administrators additionally see
  Arabic/English fonts, density, corner radius and sidebar style in Full customization.
  These settings are shared by light and dark modes.
- **Full customization** also exposes collapsible Inputs and borders sections; admin
  editors additionally receive Status colors, Neutral Palette, and Dark mode and
  personal looks. These sections keep the existing permission and field boundaries.
- **Toolbar**: Copy to light/dark theme (confirm; copies colors/effects while preserving
  background/card/input/border colors) and Reset to default (confirm). Save as profile
  lives in the Profiles header, separate from saving the active appearance.
- **Controls column + sticky compact preview**: the preview follows the full editor;
  below 64rem it moves above the controls. A native `details` summary lets the user
  collapse it, especially on small screens. `wire:ignore.self` preserves that choice
  across Livewire updates. **Save changes** stays visible outside the disclosure and
  calls the host page's existing validated `save` method, with a loading disabled state
  and a personal/everyone scope note. Neutral control/preview frames remain readable
  while the selected appearance changes.
- **Hidden data is retained**: persisted fields and every hidden containing Grid/Group
  use `dehydratedWhenHidden()`. Marking only the fields is insufficient: Filament would
  omit a hidden parent's children on save. Switching tools, mode or skin must preserve
  unsaved values. Selecting Glass over a `none` backdrop still selects Aurora for that
  mode only.
- **Validation reveals the relevant tools**: `ManagesStudioAppearance::onValidationError`
  selects Full customization on a `data.studio.*` error and switches to the light/dark
  mode containing its first invalid theme key, then delegates to the host's validation
  handler. Validation never silently drops an invalid hidden setting.
- **No field may appear twice** in the schema (same state path). Quick and full tools
  reveal a single set of fields, rather than duplicating them.

### 9.3 Live preview and mini preview
- `live-preview.blade.php`: watches `$wire.data.studio`, writes CSS variables inline on
  `<html>` for the **mode the page is in** (approximate primary scale with `color-mix`),
  sets the data-attributes, updates the lens `scale`, loads Google Fonts on demand, and
  computes contrast diagnostics using the shared readability resolver. Manual Glass uses conservative qualitative warnings rather than a base-only ratio. Exact palettes
  arrive after save (the admin page reloads when studio values changed; My appearance always reloads).
  Diagnostics live inside a native `details` popup behind the always-present "Preview
  checks" counter. The popup is absolutely positioned: warnings must never add/remove
  height above the editor, or crossing a contrast threshold while dragging a color
  picker moves the picker under the pointer and creates a feedback loop. Warning keys
  use stable indices so changing the ratio updates text without replacing the warning.
- `mini-preview.blade.php`: a self-contained mini dashboard drawn with the **theme being
  edited** (`edit_mode`), independent of the page's mode. Uses the same backdrop CSS,
  colors, angle, image and animated Aurora as the dashboard; generic localized content.
- `styles.blade.php` switches saved skin/backdrop/motion and SVG refraction when the
  page's `.dark` class changes, including outside the editor. Both themes' colors and
  effects are rendered as CSS variables. The live editor applies unsaved state for the
  page's active mode; selecting the mode to edit only changes the controls and mini preview.
- **Hard rule:** no double quote `"` anywhere inside an `x-data="…"` block (it closes the
  attribute and the script prints as text). A test enforces this for both preview views.

### 9.4 Sidebar builder
- Layout format: `['groups' => [['id' => 'g_…', 'labels' => ['en'=>…,'ar'=>…], 'collapsed' => bool]],
  'items' => [['key' => ResourceOrPageClass, 'group' => groupId|'__ungrouped', 'labels' => […],
  'icon' => 'heroicon-o-…'|null, 'hidden' => bool]]]`, both in display order.
- `StudioNavigation::catalog()` reads Filament's default sidebar for the current user
  (via `StudioNavigationManager::withoutLayout()`), labels in every locale (re-evaluates
  `getNavigationLabel()/getNavigationGroup()` per locale), and rendered icons.
- `editorState()` merges a saved layout with today's catalog: removed resources dropped,
  new ones added to their default group. `apply()` clones Filament's items, sets
  sort/label/icon, drops hidden ones, regroups; unknown items keep their original group.
  A custom item icon overrides both `icon()` and `activeIcon()` so navigation keeps
  the selected glyph on the active page. A null override preserves the resource's
  original inactive/active icons; the original navigation items are never mutated.
- `sanitize()` validates ids (`/^g_[a-z0-9_-]{1,40}$/`), icons (`/^heroicon-[os]-[a-z0-9-]+$/`),
  strips tags, limits labels to 60 chars, only configured locales.
- UI: Alpine + **SortableJS that Filament already ships** (`window.Sortable`); on drop we
  revert Sortable's DOM move and update state so Alpine's `x-for` stays authoritative;
  container is `wire:ignore`. Icon picker = one teleported dialog with all 324 outline
  Heroicons pre-rendered server-side and searchable.
- Layout is **global only** (not per user, not in profiles) — by decision so far.
- The host `AppServiceProvider` adds accordion behavior. It must only collapse
  `.fi-sidebar-group.fi-collapsible` groups: non-collapsible groups have no button
  to reopen them. Rebuilding the collapsed list also clears stale entries for
  non-collapsible groups, even when no collapsible groups remain.

### 9.5 Per-user looks (`StudioPreferences`, `MyAppearance`)
- `MyAppearance` extends Filament's `SettingsPage` (overrides `fillForm()`/`save()`),
  slug `my-appearance`, not in navigation, reachable from the **user menu**;
  `canAccess()` = signed in + plugin on panel + `user_skins` non-empty.
- Form is filled with the **effective** values (admin default + own overrides); Reset
  fills with admin defaults; Save stores only the differences.
- The admin tab always edits the **global** values (`fillStudioData()` reads
  `globalValue()`), never the admin's own personal look.

## 10. Security and robustness notes
- Hidden-field writes → `StudioTheme::sanitizeValues()` (option keys checked against
  config lists, hex validated, ranges clamped, gray palette per shade, `user_skins`
  intersected) on both admin and user saves; reads validate again.
- `cssUrl()` escapes `\ " < >` and newlines so a backdrop URL cannot break out of `<style>`.
- Fonts are only from the configured lists → no arbitrary CSS injection through font names.
- Sidebar hiding is not authorization; policies/Shield still guard routes.
- Users cannot override admin-only keys (dark-mode policy, fonts, layout, navigation),
  nor use skins outside `user_skins` — enforced in `StudioPreferences::cleanOverrides()`.

## 11. Current status (2026-10-22)

**Committed:** phases 1–3 and the first editor/profile redesign (through `93dc7f3`).

**Current experiment branch:** `codex/appearance-simplified-ux`, created from
`feature/filament-studio` with its existing uncommitted work carried forward. The branch
is not a clean baseline diff: it contains the earlier fixes and migrations below as
well as the new UX experiment. No existing profiles or settings were reset.

**Inherited uncommitted work, verified before the experiment:** the approved
skin/background editor layout, sticky generic preview, independent
backdrop palettes/colors/angle, Aurora motion controls, popup stacking fixes, profile
authorization/overwrite fixes, and inherited skin/image validation fixes. The new
2026_10_25 settings migration seeds backdrop colors from existing semantic colors,
preserving installed appearances. Existing profile rows remain intact.
The 2026_10_26 migration separates previously shared values, initializes dark values
from existing global settings, and extends legacy profile/preference JSON with the
same dark values so their prior appearance is preserved. Existing independent dark
keys in JSON are retained.
The 2026_10_27 migration adds independent main/secondary text colors, seeded from the
installed neutral palette (dark main text stays white). Applying an older profile
derives missing text colors from its own stored gray palettes. Foreground CSS targets
Filament headings, labels, values, inputs, descriptions and all sidebar item labels,
without recoloring gray backgrounds or semantic primary/status components. The editor
controls keep neutral foregrounds; the live page and mini preview use the selected
text colors. Sidebar group headings use secondary text color. Selected item text uses
main text color, while its icon and highlight retain the primary accent. The mini
preview follows the same rule. Contrast checks also use the selected foregrounds.

**UX experiment:** Quick setup and Full customization now share contextual controls;
starting palettes sit next to main color, Profiles has its own Save as profile action,
and the sticky preview supplies Save changes and a collapsible native disclosure.
All former controls remain reachable and light/dark values remain independent. Hidden
Grid/Group containers now explicitly dehydrate their contents, preventing precise
values from being lost when saving Quick setup. Validation reveals Full customization
and the affected theme when a hidden value needs correction. This experiment adds no
settings migration. Its design and acceptance checks are in `docs/UX-EXPERIMENT.md`.

**Previous verification baseline:** before this UX experiment,
`php artisan test --filter=FilamentStudio` passed **78 tests, 887 assertions**
(`FilamentStudioTest` 33 — appearance/skins, `FilamentStudioNavigationTest` 14 — sidebar
builder, `FilamentStudioPersonalTest` 31 — per-user/profiles/per-theme/backgrounds/text/upgrade).
The experiment's verification is separate and must be recorded after its checks finish.
The previously recorded full-suite run had 8 failures that **pre-date this work**
(ExampleTest `/` redirect, NotificationSystemTest ×6, SubscriptionLifecycleTest renew),
verified by running them without the Studio wiring. This is not a claim that the
current full project suite passes.

**Local data notes:** the dev database currently has the owner's own experiments saved
(red primary, Neon, collapsible sidebar, etc.). Don't "fix" them silently.

## 12. Roadmap / what remains

1. **Commit the redesign** (above). Then the owner wants further **Liquid Glass look tweaks**.
2. **Stage 4 leftovers:** move admin defaults from spatie settings into the same scoped
   storage (global → role → user) if role-level looks are wanted; JSON **export/import**
   of looks; optionally per-user sidebar layouts / sidebar in profiles; `defaultThemeMode`
   per user.
3. **Stage 5 — marketplace:** own repository, final vendor/brand name, README with
   screenshots/GIF, a **standalone settings page** shipped by the plugin (today the
   admin editor needs a host `SettingsPage`; the trait + tab make that easy), Orchestra
   Testbench tests, visual regression tests per skin × light/dark × LTR/RTL, licensing.
4. **Small known issues:** settings tab bar gets cramped at the largest font size;
   `defaultThemeMode` is read once at panel registration (global only); refraction is
   Chromium-only by platform limits; skin CSS depends on Filament 5 `.fi-*` class names
   (re-check on Filament upgrades).

## 13. How to work on it (checklist)

- Run PHP via **PowerShell**: `php artisan test --filter=FilamentStudio`.
- After editing `resources/dist/filament-studio.css` **always** run
  `php artisan filament:assets` (the browser loads the published copy; the `?v=` includes
  the source file's mtime so caches bust automatically).
- New setting: config `defaults` (+ `ranges`/option list) → `StudioSettings` property →
  **new** settings migration → `sanitizeValues()` rule if it's a new kind → editor field
  (respect the no-duplicate-field rule, add `dehydratedWhenHidden()`) → per-theme? add to
  `theme_keys` and use `themePair()` → user-overridable? add to `user_keys` → CSS
  variable in `styles.blade.php` → Arabic string → tests.
- New Alpine code in Blade attributes: **no double quotes**, end statements with `;` when
  `@js()` is followed by a newline.
- UI changes: mock first, then verify in the browser at `/admin/manage-general-settings`
  (tabs "Dashboard Appearance & Design System", "Sidebar") and `/admin/my-appearance`,
  in Arabic and in both light and dark.
- Never commit `public/js/ysfkaya/filament-phone-input/...`. Never push or merge without the owner's go-ahead.

## 14. Glossary
- **Admin defaults / global** — the `StudioSettings` values; everyone's look unless overridden.
- **Override** — a user's own value for a `user_keys` key, stored in `studio_user_preferences`.
- **Skin** — overall visual style (Classic, Liquid Glass, Neon, Soft, Minimal).
- **Solid skin** — Classic/Soft/Minimal: main color and cards per theme; all skins support page backgrounds.
- **Effect skin** — Liquid Glass/Neon: tint + effect strength; backdrop controls remain separate.
- **Profile** — a saved bundle of values; shared (admin) or personal.
- **Theme** — light or dark; each has its own set of `theme_keys`.
- **Catalog** — the default sidebar items/groups Filament builds for the current user.

## 15. Automatic Glass readability — 2026-10-28

Automatic readability is enabled by default for Liquid Glass. Light and dark have independent manual toggles under **Full customization > Style and color** (`glass_auto_readability`, `dark_glass_auto_readability`). Stored colors, opacity and profiles remain unchanged; disabling auto restores exact selected colors and opacity. The two flags bring StudioSettings to **66 properties**; the original QA07 ledger covered the then-current **64**, not these new flags.

The shared `resources/views/readability.blade.php` resolver serves saved pages, live editing and the miniature. It conservatively bounds arbitrary translucent backdrops across RGB ranges, strengthens effective card/input backing, adds a page scrim, and resolves separate body/card/input main and secondary foregrounds. It never reduces chosen opacity. Its 4.5:1 target against modeled bounds is not proof of contrast at every rendered pixel. Manual Glass diagnostics now give conservative qualitative warnings when readability is uncertain, addressing the old backdrop-warning gap. Miniature refraction remains open.

The explicit `@layer properties, theme, base, components, utilities;` prelude preserves Filament/Tailwind order when Studio loads first. The earlier flush spacing/double-logo presentation was caused by this ordering issue and is resolved, not a preexisting limitation. Canonical browser verification finds 64px total main horizontal padding, 48px card content padding and one visible logo in both desktop modes; heading/placeholder hover samples also pass after the highlight settles.

Current verification: **84 PHP tests / 966 assertions**, **8/8 math tests** (100 deterministic palettes per mode, independent contrast oracle, frozen-state/clone checks), and **9/9 QA14 browser checks**, zero page errors. The fresh final QA13 dark compatibility run is **15/15** after the layer/readability changes; earlier light/RTL **30** checks are historical. QA07 rendered-color and QA12 stress scripts now explicitly disable auto for raw-value assertions; they were syntax checked, not broadly rerun. Latest full PHP: **206 tests / 1,638 assertions; 198 passed, four errors and four failures**, the same eight previously recorded cases (`storage/app/studio-qa/qa14-full-php-suite.log`).

The local migration added only the two default-true settings. Existing colors, opacity and profile data were preserved. `filament:assets` completed successfully and the unrelated phone-input JavaScript change was restored.

Automatic Glass also selects the host's existing light/dark logo variant from the effective surface backing (`surfaceTone` / `data-studio-glass-tone`), rather than assuming the page mode determines readability. A light page with a black custom tint uses the white logo; a dark page with a white tint uses the black logo. Exactly one variant is displayed. Manual mode retains the usual page-mode logo choice. The eight math tests include backing-tone checks in both modes. Focused browser diagnostics passed **3/3**, verifying two manual warnings, no warnings with both automatic flags enabled, and one warning after disabling one flag. Branding passed **3/3**, covering both inverted mode/tint combinations and manual restoration. The warning run retained two known dashboard-login SQLite errors; branding had zero page errors. Evidence: `qa14-diagnostics-branding-review.md`, `qa14-glass-diagnostics.json`, and `qa14-glass-branding.json` under the ignored evidence directory.

A read-only boot against the original MySQL database confirmed both new automatic-readability defaults are true and both original profiles remain present (count: 2).


The additive `2026_10_28_000000_add_studio_glass_readability.php` migration adds only the two flags and does not rewrite profiles/preferences; absent keys inherit defaults. Both flags are theme keys and permitted personal settings. The shared resolver never mutates form/persisted state: effective CSS variables may differ by body, card and input role. Saved-page `.dark` changes and live mode updates use the same resolver as the miniature. Decorative highlights sit behind text; the layer prelude is required even when Studio CSS loads before Filament utilities.

The original 64-property audit and earlier full-suite 204/1,618 result remain historical. Current limits include miniature refraction fidelity, browser coverage and incomplete semantic/validation/disabled-state sampling; automatic readability is not an exhaustive accessibility guarantee.

Apply the additive migration to a prepared/cloned isolated `glass-review.sqlite` before serving it on port 8136; use matching published assets. Run from the repository root:

```powershell
& ./tests/E2E/environment.ps1 -Action php -Database glass-review.sqlite -PhpArguments @('artisan', 'migrate', '--path=packages/deal-art/filament-studio/database/settings/2026_10_28_000000_add_studio_glass_readability.php', '--force', '--no-interaction')
php artisan filament:assets
node --test tests/E2E/readability-math.test.mjs
$env:QA_BASE_URL = 'http://127.0.0.1:8136'
$env:PW_TEST_SCREENSHOT_NO_FONTS_READY = '1'
node tests/E2E/qa14-glass-readability.mjs
node tests/E2E/qa14-glass-diagnostics.mjs
$env:QA14_BRAND_ONLY = '1'
node tests/E2E/qa14-glass-diagnostics.mjs
Remove-Item Env:QA14_BRAND_ONLY
```

Set `PLAYWRIGHT_MODULE_PATH` if Playwright is not installed locally. Evidence is in ignored `storage/app/studio-qa/qa14-*` and `output/playwright/studio-qa/qa14-glass-readability/`.

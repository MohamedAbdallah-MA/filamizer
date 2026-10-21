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
| Dark theme | `dark_primary` `dark_background` `dark_surface` `dark_input` `dark_border` |
| Typography | `font_arabic` `font_latin` `font_size` (sm/md/lg/xl → 93.75–112.5 % root size) |
| Layout | `density` (compact/comfortable/spacious → `--spacing`) `radius` (none…xl → `--radius-*`) |
| Dark mode policy | `dark_mode` (toggle/light/dark) `default_theme_mode` (system/light/dark) |
| Skin | `skin` `sidebar_style` (classic/floating/collapsible) `backdrop` (none/gradient/mesh/aurora/image) `backdrop_image` |
| Effect strengths | `glass_blur` `glass_refraction` · light: `glass_opacity` `glow_intensity` `backdrop_intensity` · dark: `dark_glass_opacity` `dark_glow_intensity` `dark_backdrop_intensity` |
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
Limit: `profiles.max_per_user` (20).

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
- **Neon**: glow from `--studio-glow` on cards, primary buttons, active nav item, focused inputs.
- **Soft** / **Minimal**: shadows vs hairlines. **Classic**: Filament + colors.
- **Backdrops**: gradient/mesh scale by `--studio-backdrop-strength`
  (= intensity × 1.35 in dark); Aurora = fixed blurred blobs on `.fi-body::before`,
  animated (disabled for `prefers-reduced-motion`).
- **Sidebar styles**: floating (≥64rem, margin + radius + surface), collapsible
  (`sidebarCollapsibleOnDesktop`, server-side → applies after save).
- Editor UI classes (`.fi-studio-*`) are also in this file. Do **not** rely on Tailwind
  utilities in package views — they may not exist in Filament's pre-built CSS.

### 9.2 The editor (`StudioAppearanceEditor`)
Shared by the admin tab (`forUser: false`) and My appearance (`forUser: true`); state path
`data.studio`.
- **Simple view** (left) + **mini preview** (right, sticky on wide screens):
  Skin (ToggleButtons; users only see `user_skins`), "Customize" Light/Dark switch
  (`edit_mode`, UI-only, `dehydrated(false)`), per-theme fields via `themePair()`
  (main color always; page background + cards for solid skins; glass opacity for glass;
  glow for neon), Text size.
- **Toolbar**: Show/Hide advanced (`show_advanced`, Hidden, UI-only), Copy to other theme
  (copies colors/effects, not surfaces), Reset to default (confirm), Save as profile (modal).
- **Advanced** (admin): presets, status colors, inputs & borders (both themes), glass &
  backdrop (+ image upload), neutral palette, fonts & layout & sidebar style, dark-mode
  policy + `user_skins`. **Advanced** (user): inputs & borders, glass & backdrop (no image).
- Every persisted field uses `dehydratedWhenHidden()` so switching theme/skin/advanced
  never drops unsaved edits. Selecting Glass while backdrop is `none` auto-switches to Aurora.
- **No field may appear twice** in the schema (same state path) — that is why simple and
  advanced fields are disjoint.

### 9.3 Live preview and mini preview
- `live-preview.blade.php`: watches `$wire.data.studio`, writes CSS variables inline on
  `<html>` for the **mode the page is in** (approximate primary scale with `color-mix`),
  sets the data-attributes, updates the lens `scale`, loads Google Fonts on demand, and
  computes **WCAG contrast warnings** (glass blended over background). Exact palettes
  arrive after save (the admin page reloads when studio values changed; My appearance always reloads).
- `mini-preview.blade.php`: a self-contained mini dashboard drawn with the **theme being
  edited** (`edit_mode`), independent of the page's mode.
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
- `sanitize()` validates ids (`/^g_[a-z0-9_-]{1,40}$/`), icons (`/^heroicon-[os]-[a-z0-9-]+$/`),
  strips tags, limits labels to 60 chars, only configured locales.
- UI: Alpine + **SortableJS that Filament already ships** (`window.Sortable`); on drop we
  revert Sortable's DOM move and update state so Alpine's `x-for` stays authoritative;
  container is `wire:ignore`. Icon picker = one teleported dialog with all 324 outline
  Heroicons pre-rendered server-side and searchable.
- Layout is **global only** (not per user, not in profiles) — by decision so far.

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

**Committed:** phases 1–3 (`e653dad`, `79e3d89`, `fd5bada`).

**Uncommitted but working and tested** in the working tree on `feature/filament-studio`:
the redesign (simple editor + advanced toggle + mini preview), per-theme light/dark
values, profiles, Reset to default, per-user overrides, My appearance page, save-time
sanitizing, the 2026_10_24 settings migration and the two new tables. The owner was
about to try it before committing. Commit it with the files under
`packages/deal-art/filament-studio`, `public/css/deal-art`, and the three
`tests/Feature/FilamentStudio*Test.php` files (exclude the phone-input JS).

**Tests:** `php artisan test --filter=FilamentStudio` → **62 passing, 739 assertions**
(`FilamentStudioTest` 33 — appearance/skins, `FilamentStudioNavigationTest` 14 — sidebar
builder, `FilamentStudioPersonalTest` 15 — per-user/profiles/per-theme). Full suite: 8 failures that
**pre-date this work** (ExampleTest `/` redirect, NotificationSystemTest ×6,
SubscriptionLifecycleTest renew) — verified by running them without the Studio wiring.

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
- **Solid skin** — Classic/Soft/Minimal: user picks page background, cards, main color per theme.
- **Effect skin** — Liquid Glass/Neon: main color + effect strength per theme.
- **Profile** — a saved bundle of values; shared (admin) or personal.
- **Theme** — light or dark; each has its own set of `theme_keys`.
- **Catalog** — the default sidebar items/groups Filament builds for the current user.

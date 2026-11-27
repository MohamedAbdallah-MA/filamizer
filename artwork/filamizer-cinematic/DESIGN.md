# Filamizer — one minute to make it yours

Mode: autonomous cinematic creation, approved by the user. 60.000 seconds, 1800 frames at 30fps, 1920×1080, English. Audience: Laravel/Filament developers and panel administrators. Deliver an editable Remotion composition and the Shotcraft cinematic production output. Preserve the earlier video project.

## Product evidence and boundaries

README.md, config/filamizer.php, src/Filament/StudioAppearanceEditor.php, src/Filament/Pages/ManageAppearance.php, and the actual local editor are the content sources. Filamizer is an independent pre-release Filament 5 plugin for runtime appearance customization without compiling a custom theme. PHP 8.2+, supported Laravel and database-backed Spatie Settings are prerequisites. Composer package availability is not claimed: installation copy explicitly says to configure the local Composer path repository first. Global editing must be authorized with canCustomize(). Shared defaults and personal overrides are separate. Hiding sidebar entries does not grant or revoke access.

## Requirements → execution

| Requirement | Decision |
|---|---|
| One minute | Eight scenes totaling exactly 1800 frames |
| Explain the plugin | Opening states runtime customization for Filament 5 |
| Show how to use | Local installation, registration, editor navigation, controls, Save changes, profiles, My appearance |
| Cinematic | Dark indigo stage, restrained perspective, measured push-ins, real UI textures, deliberate holds |
| SFX | Local CC0 Freesound cinematic whooshes, impact and physical mouse clicks; centralized frame cues |
| Product truth | Actual plugin rendered in an isolated Testbench filming host; no recreated application UI |
| Privacy | New SQLite database with Demo Creator / creator@example.test; miniature metrics belong to the plugin's illustrative preview |

## Visual direction

Considered: bright editorial tutorial; dark indigo cinema; paper/Ink Press. Selected dark indigo cinema because Royal Indigo is the shipped default and the plugin's glass/dark controls deserve contrast. Reject paper because it replaces the product's own visual cues.

Tokens from the package: primary #4F46E5, bright accent #818CF8, background #030712, surface #111827, light surface #FFFFFF, muted #9CA3AF, light canvas #F9FAFB, border #1F2937. Inter variable font copied from the actual Filament host. Headline 86–112px, step title 72–86px, instruction 58–64px, auxiliary 34px, code 35–40px. 96px side safe area, 24px stage corners, 8px spacing grid. Large text has line-height 1.06–1.15 and tight tracking. No decorative UI metrics. Product screenshots retain their own typography and spacing; small screenshot text is contextual texture, while required instructions are repeated as large overlays.

Motion: professional/premium, bezier(0.33,0,0.15,1), entrances 24–42f, no camera shake. Information holds ≥30f. No repeated dominant effect. Setup code uses custom editorial animation because a screenshot camera card cannot explain a plugin registration snippet. Light/dark uses a single custom comparison wipe; sidebars use a custom detail pan. Only use cards where their semantics fit.

## Card mapping and implementation sources

- cursor-flyover / cursor-flyover: gallery/api/library.json; references/shots/camera/cursor-flyover.md; demos/camera/cursor-flyover/CursorFlyover.tsx. Adapt to real editor coordinates. Preserve cursor leading camera by 2f, 16f cursor move, 20f camera travel, 22f click, 18f ripple; lengthen reading holds for a tutorial. Use captured before/after states to make clicks produce real responses.
- outro-group-photo-launch / outro-group-photo-launch: gallery/api/library.json; references/shots/outro/outro-group-photo-launch.md; demos/outro/outro-group-photo-launch/OutroGroupPhotoLaunch.tsx. Preserve 12f fly-in with bezier(0.34,1.4,0.44,1), staggered arrivals, 4° crane, 42f wordmark cue, 58–70f rule, ≥30f final hold. Replace gold/paper with indigo and actual Filamizer control cutouts. Reference media URLs are in gallery/api/library.json; no user-selected reference clip.
- PageCam from assets/lib/PageCam.tsx copied locally for all full-page screenshot camera shots.
- Other shots are original brand/instruction compositions, not claimed as Gallery reproductions. Tutorial time and readable labels take priority over maximal shot count.

## Final storyboard (production released)

| # | Time / frames | Content and source | Camera, caption, SFX |
|---|---|---|---|
| 1 | 0–6s / 0–179 | Filamizer; Style your Filament panel. Real miniature cutout | One product plane reveals on indigo stage; slow sideways push, ≥1s title hold; soft whoosh and low impact |
| 2 | 6–14s / 180–419 | Set up locally. PHP 8.2+ · Filament 5. Configure local Composer path repository, require ^0.1, installer; register plugin with authorization | Original code typography, 2 readable beats; keyboard/click/whoosh; no published-package claim |
| 3 | 14–20s / 420–599 | Open Dashboard appearance, choose a skin; Classic / Liquid Glass / Neon / Soft / Minimal | Real editor overview → skin detail; click selects Liquid Glass and actual captured result |
| 4 | 20–30s / 600–899 | Tune background, fonts, density and corners in Full customization | Cursor-led tour adapted from cursor-flyover, measured holds, real control cutouts and after-states; clicks and movement swooshes |
| 5 | 30–37s / 900–1109 | Style light and dark separately | Actual light and dark miniatures + mode control; single comparison reveal; caption states independent saves; soft wipe |
| 6 | 37–44s / 1110–1319 | Arrange your sidebar: reorder, group, rename, hide | Actual Sidebar Builder screenshot, focus sweep; explicit visibility-only note; restrained clicks |
| 7 | 44–54s / 1320–1619 | Save changes applies shared defaults. Save as profile stores reusable look. My appearance creates a personal override | Actual save control, readable saved-profile card, personal page captures, 3 sequential instructions; clicks/short whoosh |
| 8 | 54–60s / 1620–1799 | Filamizer. Make your panel feel like yours. Independent Filament 5 plugin · pre-release | Real cutout group-photo launch adapted from exact demo, wordmark stamp at +42f, ≥1s clean hold; whoosh → impact → soft tail |

Audio is SFX only: no BGM or voiceover requested. On-screen instruction copy carries the explanation. Source files are fixed local assets with license URLs, hashes and measured peaks. No synthetic notification/game feedback. Output timing must be measured from decoded final audio rather than assumed AAC offset.

## Verification plan

Capture actual pages at 2×, record element bounds in layout.json, verify keyframes for all eight shots and every section boundary, inspect a 480px contact sheet for headline legibility, check 1800 video frames / 60s and AAC audio, measure mix peak, run TypeScript, and obtain an independent final review following the two requested skills. Limitations (including any unlistened audio) must be recorded honestly.

## Final adaptation record

The profile action modal did not become visible in the isolated filming host, so the tutorial uses an actual rendered profile card plus the explicit instruction “Name your look. Then choose Save profile.” It does not depict a fabricated dialog or claim to be an uninterrupted screen recording. The fictional profile is seeded in the filming database. The tour now uses actual Quick setup, Mesh-selected, font-dropdown-open and Compact/Rounded captures, aligned to their measured page coordinates. No names or client data from a real host application are used. Rendering uses Remotion for the picture; the identical frame cue table is mixed offline and muxed as AAC to avoid the sparse-frame Windows inline mixer failure.

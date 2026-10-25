# Appearance editor: simpler setup

Experiment branch: `codex/appearance-simplified-ux`.

The client should be able to choose a look without learning the design system.
The existing Filament components, Arabic typography and RTL layout remain the
visual system. The direction is a calm, practical editor with fewer initial choices.

## Structure

```text
Profiles                                      Save as profile
Saved looks (each includes light + dark)

Editing: Light / Dark       Quick setup / Full customization

Style and color                         Live preview
  Visual skin choices                   Mode being edited
  Main color + optional starting palette
                                        Save changes
Page background                         Scope of this save
  Type, base color, palette, motion

Reading and layout
  Text size

Copy to other mode / Reset
```

Full customization reveals text and surface colors, skin effects and exact
background controls next to their related choices. It also exposes fonts, layout,
inputs, borders, status colors, neutral shades and administrator policy controls.
Both views use the same form fields and state: changing the view never resets a look.

Profiles stay visible, preserve both modes and retain their existing ownership
rules. Starting palettes affect only the mode being edited. The preview stays
generic and sticky; on small screens it can be collapsed to make room for controls.
No database migration or settings reset is needed for this experiment.

## Acceptance checks

- Quick setup reduces initial choices; every existing setting remains reachable.
- Light and dark edits survive view changes, profile use and saving.
- Profile and main save actions have distinct, explicit wording.
- Arabic and English labels, focus states and mobile layout remain usable.
- Color picker popups remain above later controls and the preview does not block them.
- Existing authorization, inheritance and profile regression tests continue to pass.

# Filamizer feature film

56-second English product showcase. Two separately composed versions:

- `exports/filamizer-landscape-1080p.mp4`: 1920 × 1080, 30 fps.
- `exports/filamizer-vertical-1080p.mp4`: 1080 × 1920, 30 fps.
- Matching JPEG posters and a storyboard for each format.

H.264 video, AAC stereo audio, fast-start MP4. Original synthesized electronic
soundtrack, on-screen copy, no voiceover. No stock music or paid assets.

## Story

| Time | Feature |
| --- | --- |
| 00:00–00:06 | Your dashboard. Your style. |
| 00:06–00:14 | Classic, Liquid Glass, Neon, Soft, Minimal |
| 00:14–00:22 | Independent light and dark appearance |
| 00:22–00:30 | Gradients, mesh, animated aurora, image backgrounds |
| 00:30–00:38 | Colors, Latin/Arabic font support, density, corner radius |
| 00:38–00:45 | Sidebar ordering and layout styles |
| 00:45–00:50 | Shared and personal profiles |
| 00:50–00:56 | Filamizer closing brand card |

The dashboard panels are **stylized motion illustrations**, based on the package's
miniature preview and verified configuration options. They are not application
screen recordings. Sample dashboard metrics are illustrative; Filamizer does not
bundle those business modules. The video makes no availability, price, performance,
official affiliation, or release claim. It does not advertise a published Composer
installation command, because the package is marked pre-release.

## Edit and render

Requires Node.js and Windows Segoe UI fonts. Font paths are at the top of
`render.cjs`; replace them with licensed local fonts when rendering on other OSes.

```powershell
cd artwork/video
npm ci
node render.cjs previews
node render.cjs all
# Or render one version:
node render.cjs landscape
node render.cjs vertical
```

All copy, timing, illustration, motion, and music generation live in `render.cjs`.
Delete `soundtrack.wav` before rerendering if you edit the audio synthesis code.
Dependencies and generated media are ignored by Git. This directory is already
excluded from Composer distribution through the package's artwork exclusion.

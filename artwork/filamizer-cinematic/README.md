# Filamizer — cinematic tutorial

Exactly **60 seconds**, 1920×1080, 30fps. English on-screen instruction, actual Filamizer editor captures, 21 synchronized CC0 sound cues. No voiceover or background music. The earlier `artwork/video` project and its exports are preserved.

## Watch and edit

- Final video: `exports/filamizer-cinematic-60s.mp4` (H.264 + AAC stereo, fast-start).
- Poster: `exports/filamizer-cinematic-poster.jpg`.
- Remotion preview: http://localhost:3127/Filamizer-Cinematic
- Visual editor: http://localhost:5198/ — scenes and SFX are separate timeline clips; scene titles and accent colors are editable. Other instructional copy is in `src/Film.tsx`.

Start the preview with `npm run dev`. Each scene also has its own composition. The main composition is `Filamizer-Cinematic` and its `sound` prop enables/disables SFX.

## Rebuild

From this folder, install Node dependencies and the local audio-analysis environment:

```powershell
npm ci
python -m venv .venv
.\.venv\Scripts\python -m pip install -r requirements.txt
npm run export
```

`npm run export` renders the Remotion picture, mixes the same cue table offline, assembles AAC audio into the MP4, checks audio alignment, and regenerates the poster. This pipeline avoids a Windows inline-audio error observed during sparse-frame QA. `ffmpeg-static` supplies the local encoder. Rendering uses Remotion's bundled browser.

## Story

| Time | Content |
|---|---|
| 00–06s | Runtime customization without compiling a theme |
| 06–14s | Local Composer setup, installer and explicit editor authorization |
| 14–20s | Dashboard appearance and five skins |
| 20–30s | Full customization, backgrounds, fonts, density and corners |
| 30–37s | Independent light and dark modes |
| 37–44s | Sidebar grouping, ordering and visibility |
| 44–54s | Shared defaults, reusable profiles and personal appearance |
| 54–60s | Cinematic feature montage and Filamizer sign-off |

## Accuracy and capture notes

Filamizer is an independent **pre-release Filament 5 plugin**. The installation scene requires a local Composer path repository first and does not claim public package availability. The sample Gate permission must be defined in the host application. The package README is the full setup reference.

All product imagery is captured from the actual package in a separate Testbench host using a new SQLite database and a fictional Demo Creator identity. The miniature's sample Customers/Projects metrics are part of Filamizer's own preview. The video is an edited sequence of real state captures, not an uninterrupted screen recording. Profile creation is explained with written instructions and the actual saved profile card; the name-entry dialog was not demonstrated.

The optional `demo/` host is loopback-only. It has a convenience demo login and must remain a local filming fixture. Product source is unchanged.

## Sources and review

- `DESIGN.md`: brief, visual tokens, card mapping and storyboard.
- `REVIEW.md`: independent review and remaining limitations.
- `out/audio-qa.json`: mix peak and decoded audio alignment probes.
- `out/qa/encoded/`: frames extracted from the delivered MP4.
- `public/audio/sfx/FREESOUND-CREDITS.md`: sound sources, exact CC0 licenses and attribution details.
- `public/audio/sfx/audio-manifest.json`: preview-file hashes, analysis and download provenance.
- Font: Filament's bundled Inter variable font; license in `public/INTER-LICENSE.txt`.

The sound assets are Freesound's high-quality MP3 previews, not original lossless recordings. No aural-review claim is made from waveform measurements alone.

The video uses Remotion and the Video Shotcraft workflow. Its source is editable; the renderer has its own license. Optional creator credits: [X](https://x.com/VincentWei93), [Douyin](https://www.douyin.com/user/MS4wLjABAAAAK1pkjBxilk2Oi_9h_vFyD-lTAu9CTlvhmOtkosDvvxg), [Xiaohongshu](https://xhslink.cn/m/At9iP2d5C1V). Optional [showcase](https://vincentwei1021.github.io/video-shotcraft/showcase.html) and [submission form](https://github.com/Vincentwei1021/video-shotcraft/issues/new?template=showcase.yml). A Jianying editing-project export can also be produced on request.

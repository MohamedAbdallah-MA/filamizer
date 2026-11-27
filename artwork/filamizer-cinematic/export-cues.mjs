import fs from 'node:fs';
import {SFX,SHOTS,TOTAL,OUTPUT_AUDIO_OFFSET_F} from './src/timeline.ts';
fs.mkdirSync('out',{recursive:true});
fs.writeFileSync('out/cues.json',JSON.stringify({fps:30,total:TOTAL,shots:SHOTS,outputAudioOffsetFrames:OUTPUT_AUDIO_OFFSET_F,cues:SFX},null,2));

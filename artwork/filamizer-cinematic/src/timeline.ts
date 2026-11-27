export const TOTAL=1800;
export const SHOTS={
hero:{from:0,duration:180,label:'Introduction'},setup:{from:180,duration:240,label:'Install and authorize'},
skins:{from:420,duration:180,label:'Choose a skin'},tune:{from:600,duration:300,label:'Full customization'},
modes:{from:900,duration:210,label:'Light and dark'},sidebar:{from:1110,duration:210,label:'Sidebar builder'},
save:{from:1320,duration:300,label:'Save and personalize'},outro:{from:1620,duration:180,label:'Finale'}};
export const SOURCE_PEAK_F:Record<string,number>={'fs-812675.mp3':2.55,'fs-475896.mp3':4.05,'fs-687107.mp3':4.35,'fs-755055.mp3':10.95};
export const OUTPUT_AUDIO_OFFSET_F=0;
const cue=(target:number,file:string,volume:number,duration:number,action:string)=>({from:Math.max(0,Math.round(target-SOURCE_PEAK_F[file]-OUTPUT_AUDIO_OFFSET_F)),target,src:`audio/sfx/${file}`,volume,duration,action});
export const SFX=[
cue(SHOTS.hero.from+20,'fs-812675.mp3',.36,51,'Product plane reveal'),cue(SHOTS.hero.from+62,'fs-755055.mp3',.28,83,'Opening wordmark settles'),
cue(SHOTS.setup.from+12,'fs-475896.mp3',.26,12,'Setup chapter'),cue(SHOTS.setup.from+124,'fs-475896.mp3',.22,12,'Registration snippet'),
cue(SHOTS.skins.from+16,'fs-812675.mp3',.30,51,'Preview arrives'),cue(SHOTS.skins.from+80,'fs-687107.mp3',.85,8,'Liquid Glass selection'),
cue(SHOTS.tune.from+44,'fs-687107.mp3',.9,8,'Full customization click'),cue(SHOTS.tune.from+110,'fs-687107.mp3',.8,8,'Background stop'),
cue(SHOTS.tune.from+176,'fs-687107.mp3',.9,8,'Typography stop'),cue(SHOTS.tune.from+242,'fs-687107.mp3',.8,8,'Layout stop'),
cue(SHOTS.modes.from+22,'fs-475896.mp3',.26,12,'Light reveal'),cue(SHOTS.modes.from+76,'fs-812675.mp3',.34,51,'Dark reveal'),
cue(SHOTS.sidebar.from+16,'fs-475896.mp3',.26,12,'Sidebar chapter'),cue(SHOTS.sidebar.from+88,'fs-687107.mp3',.85,8,'Add group'),
cue(SHOTS.save.from+30,'fs-687107.mp3',.9,8,'Save changes'),cue(SHOTS.save.from+105,'fs-475896.mp3',.28,12,'Profile action'),
cue(SHOTS.save.from+168,'fs-687107.mp3',.9,8,'Save profile'),cue(SHOTS.save.from+214,'fs-475896.mp3',.26,12,'Personal appearance'),
cue(SHOTS.outro.from+16,'fs-812675.mp3',.42,51,'Group-photo assembly'),cue(SHOTS.outro.from+42,'fs-755055.mp3',.48,83,'Final wordmark stamp'),
cue(SHOTS.outro.from+70,'fs-475896.mp3',.18,12,'Signature rule')];

import React from 'react';
import {AbsoluteFill,Easing,Img,Sequence,staticFile,useCurrentFrame} from 'remotion';
import {Audio} from '@remotion/media';
import {PageCam,type CamKey} from './PageCam';
import layout from './layout.json';
import {SHOTS,SFX,TOTAL} from './timeline';

const INK='#030712',ACC='#a5b4fc',WHITE='#f9fafb',MUTED='#b6bece';
const ease=Easing.bezier(.33,0,.15,1),flight=Easing.bezier(.34,1.4,.44,1);
const clamp=(v:number)=>Math.max(0,Math.min(1,v));
const ramp=(f:number,s:number,d:number,e=ease)=>e(clamp((f-s)/d));
const mix=(a:number,b:number,t:number)=>a+(b-a)*t;
type Props={title?:string;accent?:string;duration?:number};
export const FilmStyles=()=> <style>{`@font-face{font-family:FilmInter;src:url('${staticFile('inter.woff2')}') format('woff2');font-weight:100 900;font-display:block}*{box-sizing:border-box}h1,h2,p{margin:0}body{margin:0}`}</style>;
export const Stage:React.FC<{children?:React.ReactNode;accent?:string}>=({children,accent=ACC})=><AbsoluteFill style={{background:INK,color:WHITE,fontFamily:'FilmInter,Inter,Arial,sans-serif',overflow:'hidden'}}>
 <FilmStyles/><AbsoluteFill style={{background:'radial-gradient(ellipse 1000px 680px at 1280px 550px,#312e6355,transparent 78%)'}}/>
 <div style={{position:'absolute',left:96,right:96,top:70,height:1,background:'linear-gradient(90deg,#818cf860,transparent)'}}/>{children}
 <div style={{position:'absolute',right:98,top:96,fontSize:28,color:accent,letterSpacing:1.5}}>FILAMIZER</div>
</AbsoluteFill>;
const Heading:React.FC<{step:string;title:string;accent?:string}>=({step,title,accent=ACC})=>{const f=useCurrentFrame(),t=ramp(f,0,30);return <div style={{position:'absolute',left:96,top:98,opacity:t,translate:`0 ${(1-t)*24}px`}}><div style={{fontSize:32,color:accent,letterSpacing:3,fontWeight:570,marginBottom:22}}>{step}</div><h2 style={{fontSize:82,fontWeight:690,lineHeight:1.08,letterSpacing:-3}}>{title}</h2></div>;};
const Note:React.FC<{children:React.ReactNode;top?:number;size?:number;width?:number;color?:string}>=({children,top=900,size=58,width=1728,color=WHITE})=><div style={{position:'absolute',left:96,top,width,fontSize:size,fontWeight:480,lineHeight:1.18,letterSpacing:-1.4,color,whiteSpace:'pre-line'}}>{children}</div>;
const Texture:React.FC<{name:string;style?:React.CSSProperties}>=({name,style})=><Img src={staticFile(`textures/${name}.png`)} style={{display:'block',width:'100%',...style}}/>;
const Window:React.FC<{children:React.ReactNode;x:number;y:number;w:number;h:number;style?:React.CSSProperties}>=({children,x,y,w,h,style})=><div style={{position:'absolute',left:x,top:y,width:w,height:h,borderRadius:24,overflow:'hidden',background:'#111827',border:'1px solid #818cf83d',boxShadow:'0 35px 100px #0007',...style}}>{children}</div>;
const Camera:React.FC<{name:keyof typeof layout;keys:CamKey[];w:number;h:number;frame?:number;children?:React.ReactNode}>=({name,keys,w,h,frame,children})=>{const s=w/1920;return <div style={{position:'absolute',width:1920,height:1080,left:0,top:h/2-540*s,scale:s,transformOrigin:'0 0'}}><PageCam src={`textures/${layout[name].file}`} pageH={layout[name].pageH} keys={keys} frame={frame}>{children}</PageCam></div>;};
const Pointer:React.FC<{press?:number}>=({press=0})=><svg width="44" height="44" viewBox="0 0 24 24" style={{filter:'drop-shadow(0 3px 4px #0008)',scale:1-.16*press,transformOrigin:'4px 2px'}}><path d="M4.5 2.5 L4.5 19.2 L8.9 15 L11.9 21.6 L14.9 20.3 L11.9 13.8 L18.2 13.4 Z" fill="#0d1324" stroke="#ffffff" strokeWidth="1.6" strokeLinejoin="round"/></svg>;

export const Hero:React.FC<Props>=({title='Your panel.\nYour signature.',accent=ACC})=>{const f=useCurrentFrame(),p=ramp(f,8,54),h=ramp(f,30,32);return <Stage accent={accent}>
 <div style={{position:'absolute',left:96,top:128,fontSize:34,letterSpacing:3,color:accent,opacity:ramp(f,0,30)}}>FILAMIZER / FILAMENT 5</div>
 <div style={{position:'absolute',left:96,top:295,width:880,fontSize:112,fontWeight:740,lineHeight:1.03,letterSpacing:-5,opacity:h,translate:`0 ${(1-h)*32}px`}}>{title.split('\n')[0]}<br/><span style={{color:accent}}>{title.split('\n')[1]}</span></div>
 <div style={{position:'absolute',left:96,top:625,width:830,fontSize:57,lineHeight:1.18,letterSpacing:-1.8,color:MUTED,opacity:ramp(f,62,24)}}>Customize your dashboard<br/>without compiling a theme.</div>
 <div style={{position:'absolute',left:1040,top:330,width:920,perspective:1600,opacity:p,translate:`${(1-p)*180}px 0`}}><div style={{transform:`rotateY(${-18+6*p}deg) rotateX(3deg) translateZ(${30*p}px)`,borderRadius:24,overflow:'hidden',boxShadow:'-30px 40px 100px #0009',border:'1px solid #a5b4fc60'}}><Texture name="glass-mini" style={{width:920}}/></div></div>
 <div style={{position:'absolute',left:96,top:927,fontSize:34,color:MUTED,opacity:ramp(f,94,24)}}>Five skins. Fonts. Backgrounds. Profiles.</div>
 </Stage>;};

export const Setup:React.FC<Props>=({title='A few lines. A new look.',accent=ACC})=>{const f=useCurrentFrame(),register=f>=120,t=ramp(f,register?120:12,24);return <Stage accent={accent}>
 <Heading step="01 / SET UP LOCALLY" title={title} accent={accent}/><Note top={295} size={43} color={MUTED}>PHP 8.2+ · Filament 5 · Database-backed Spatie Settings</Note>
 <Window x={96} y={386} w={1728} h={390}><div style={{padding:'40px 48px',fontFamily:'Consolas,monospace',fontSize:register?36:40,lineHeight:1.55,opacity:t,translate:`0 ${(1-t)*14}px`}}>{register?<><div style={{color:accent}}>use Filamizer\FilamizerPlugin;</div><div style={{color:accent}}>use Filament\Facades\Filament;</div><div>$panel-&gt;plugin(FilamizerPlugin::make()</div><div style={{paddingLeft:50}}>-&gt;canCustomize(fn (): bool =&gt;</div><div style={{paddingLeft:100}}>Filament::auth()-&gt;user()?-&gt;can('manage-panel-appearance') ?? false));</div></>:<><div style={{color:accent,fontSize:34,marginBottom:20}}># Configure the local Composer path repository first</div><div>composer require filamizer/filamizer:^0.1</div><div>php artisan filamizer:install --migrate</div></>}</div></Window>
 <Note top={845} size={58}>{register?'Register the plugin. Authorize shared editing.':'Require the local package. Run the installer.'}</Note>
 <Note top={961} size={32} color={MUTED}>{register?'Define this permission in your Gate, or use your own authorization rule.':'Pre-release: the local repository setup is documented in README.md.'}</Note>
 </Stage>;};

export const Skins:React.FC<Props>=({title='Five skins. One click.',accent=ACC})=>{const f=useCurrentFrame(),selected=f>=80;return <Stage accent={accent}>
 <Heading step="02 / CHOOSE YOUR LOOK" title={title} accent={accent}/><Note top={329} size={58} width={780}>Open <span style={{color:accent}}>Dashboard<br/>appearance.</span></Note><Note top={515} size={44} width={780} color={MUTED}>{'Choose a skin.\nWatch the preview update.'}</Note>
 <Window x={940} y={320} w={884} h={408} style={{opacity:ramp(f,0,32),transform:`perspective(1600px) rotateY(${mix(-9,0,ramp(f,0,70))}deg)`}}><Texture name={selected?'glass-mini':'classic-mini'}/></Window>
 <Window x={96} y={792} w={1728} h={225} style={{background:'#fff'}}><Texture name={selected?'glass-skins':'classic-skins'} style={{width:1728}}/><div style={{position:'absolute',left:mix(400,450,ramp(f,58,16)),top:136,opacity:ramp(f,44,12)*(1-ramp(f,111,12))}}><Pointer press={f>=79&&f<=83?1:0}/></div>{f>=80&&f<98?<div style={{position:'absolute',left:410,top:88,width:80,height:80,borderRadius:100,border:`3px solid ${accent}`,scale:mix(.3,1.5,(f-80)/18),opacity:1-(f-80)/18}}/>:null}</Window>
 </Stage>;};

// Exact CursorFlyover grammar: hand leads eye by 2f; 16f hand, 20f camera,
// click at +22f; 18f ripple. Tutorial holds expanded from the reference.
const starts=[22,88,154,220],camEase=Easing.bezier(.55,0,.18,1);
const cams=[{x:1120,y:1130,s:.9},{x:880,y:690,s:1.65},{x:880,y:2040,s:1.7},{x:880,y:2720,s:1.75},{x:880,y:2805,s:1.75}];
const targets=[{x:764,y:780},{x:790,y:1956},{x:1006,y:2734},{x:720,y:2827}];
const tourCam=(f:number)=>{let{x,y,s}=cams[0],hop=0;starts.forEach((a,k)=>{const u=ramp(f,a+2,20,camEase),to=cams[k+1];x=mix(x,to.x,u);y=mix(y,to.y,u);s=mix(s,to.s,u);if(k>0&&u>0&&u<1)hop=.08*Math.sin(Math.PI*u);});const b=ramp(f,266,24,Easing.inOut(Easing.cubic));return{x:mix(x,1120,b),y:mix(y,2150,b),s:mix(s,.9,b)*(1-hop)};};
const tourCursor=(f:number)=>{let x=1100,y=750;starts.forEach((a,k)=>{const u=ramp(f,a,16),to=targets[k],dx=to.x-x,dy=to.y-y,len=Math.max(1,Math.hypot(dx,dy)),bow=Math.sin(Math.PI*u)*Math.min(90,len*.12);x=mix(x,to.x,u)-dy/len*bow;y=mix(y,to.y,u)+dx/len*bow;});return{x,y};};

const TourTexture:React.FC<{f:number;cam:{x:number;y:number;s:number};w:number;h:number}>=({f,cam,w,h})=>{
 const name:keyof typeof layout=f<44?'glass':f>=110&&f<154?'mesh':f>=176&&f<220?'fonts-open':f>=242&&f<266?'tuned':'advanced';
 const delta=name==='mesh'?layout.mesh.boxes.background.y-layout.advanced.boxes.background.y:name==='fonts-open'?layout['fonts-open'].boxes.reading.y-layout.advanced.boxes.reading.y:name==='tuned'?layout.tuned.boxes.reading.y-layout.advanced.boxes.reading.y:0;
 return <Camera name={name} w={w} h={h} frame={f} keys={[{frame:0,cx:cam.x,cy:cam.y+delta,zoom:cam.s}]}/>;
};

export const Tune:React.FC<Props>=({title='Fine-tune the details.',accent=ACC})=>{const f=useCurrentFrame(),cam=tourCam(f),cur=tourCursor(f),k=f<88?0:f<154?1:f<220?2:3,w=1110,h=645,s=w/1920,cx=w/2+(cur.x-cam.x)*cam.s*s,cy=h/2+(cur.y-cam.y)*cam.s*s,pf=f-starts[k]-22,r=pf>=0&&pf<18?pf/18:-1;return <Stage accent={accent}>
 <Heading step="03 / FULL CUSTOMIZATION" title={title} accent={accent}/><Note top={370} size={58} width={600}>{['Open Full\ncustomization.','Set your\nbackground.','Choose Latin\nand Arabic fonts.','Adjust density\nand corners.'][k]}</Note><Note top={596} size={39} width={600} color={MUTED}>{['Go beyond quick setup.','Color, gradient, mesh,\nAurora or your own image.','Keep your dashboard\ncomfortable to read.','Make space work\nthe way you do.'][k]}</Note>
 <Window x={714} y={315} w={w} h={h}><TourTexture f={f} cam={cam} w={w} h={h}/>{r>=0?<div style={{position:'absolute',left:cx-40,top:cy-40,width:80,height:80,borderRadius:80,border:`3px solid ${accent}`,scale:.3+1.2*r,opacity:1-r}}/>:null}<div style={{position:'absolute',left:cx,top:cy,opacity:1-ramp(f,266,18)}}><Pointer press={pf>=-1&&pf<3?1:0}/></div></Window>
 </Stage>;};

export const Modes:React.FC<Props>=({title='Light and dark. Independently.',accent=ACC})=>{const f=useCurrentFrame(),r=ramp(f,54,40,Easing.inOut(Easing.cubic));return <Stage accent={accent}>
 <Heading step="04 / TWO MODES, TWO LOOKS" title={title} accent={accent}/><div style={{position:'absolute',left:96,top:332,fontSize:34,color:MUTED,letterSpacing:2}}>LIGHT THEME</div><div style={{position:'absolute',left:1008,top:332,fontSize:34,color:accent,letterSpacing:2,opacity:r}}>DARK THEME</div>
 <Window x={96} y={400} w={816} h={377}><Texture name="glass-mini"/></Window><Window x={1008} y={400} w={816} h={377} style={{clipPath:`inset(0 ${(1-r)*100}% 0 0)`}}><Texture name="dark-mini"/></Window>{r>0&&r<1?<div style={{position:'absolute',left:1008+816*r,top:400,height:377,width:3,background:accent,boxShadow:`0 0 24px ${accent}`}}/>:null}
 <Note top={853} size={58}>Choose Light theme or Dark theme.</Note><Note top={940} size={42} color={MUTED}>Each keeps its own skin, colors and effects.</Note>
 </Stage>;};

export const Sidebar:React.FC<Props>=({title='Give navigation a better flow.',accent=ACC})=>{const f=useCurrentFrame(),after=f>=88;return <Stage accent={accent}>
 <Heading step="05 / SIDEBAR BUILDER" title={title} accent={accent}/><div style={{position:'absolute',left:96,top:360,fontSize:68,fontWeight:600,lineHeight:1.35,letterSpacing:-2}}>Reorder.<br/>Group.<br/><span style={{color:accent}}>Rename.</span></div>
 <Window x={650} y={337} w={1174} h={518} style={{background:'#fff',transform:`perspective(1600px) rotateY(${mix(6,0,ramp(f,0,50))}deg)`}}><Texture name={after?'sidebar-group-builder':'sidebar-builder'} style={{width:1174,translate:`0 ${after?-30:35}px`}}/><div style={{position:'absolute',right:180,top:107,opacity:ramp(f,52,16)*(1-ramp(f,108,12))}}><Pointer press={f>=87&&f<=91?1:0}/></div></Window>
 <Note top={899} size={56}>Open Sidebar to organize your navigation.</Note><Note top={982} size={34} color={MUTED}>Hiding an item changes visibility. Permissions still control access.</Note>
 </Stage>;};

export const Save:React.FC<Props>=({title='Save it. Keep it. Make it yours.',accent=ACC})=>{const f=useCurrentFrame(),part=f<96?0:f<204?1:2,copy=[['Save changes','Apply the shared\ndefault appearance.'],['Save as profile','Name your look.\nThen choose Save profile.'],['My appearance','Let each person keep\ntheir own look.']][part];return <Stage accent={accent}>
 <Heading step="06 / SAVE & PERSONALIZE" title={title} accent={accent}/><Note top={362} size={70} width={715} color={accent}>{copy[0]}</Note><Note top={515} size={50} width={650}>{copy[1]}</Note>
 {part===0?<><Window x={884} y={372} w={940} h={435}><Texture name="glass-mini"/></Window><Window x={884} y={842} w={940} h={79} style={{borderRadius:14}}><Texture name="glass-save"/></Window><div style={{position:'absolute',left:1360,top:868,opacity:1-ramp(f,56,16)}}><Pointer press={f>=29&&f<=33?1:0}/></div></>:part===1?<Window x={872} y={370} w={952} h={400} style={{background:'#fff'}}><div style={{padding:70}}><div style={{fontSize:34,color:'#4f46e5',marginBottom:36}}>YOUR SAVED LOOK</div><Texture name="profile-card" style={{width:800}}/></div></Window>:<Window x={872} y={310} w={952} h={650}><Camera name={'personal' as keyof typeof layout} w={952} h={650} keys={[{frame:0,cx:1100,cy:600,zoom:1.12},{frame:96,cx:1100,cy:690,zoom:1.12}]}/></Window>}
 <Note top={950} size={36} color={MUTED}>{part===0?'Shared defaults are managed by authorized editors.':part===1?'A saved profile includes both light and dark modes.':'Find My appearance in the user menu.'}</Note>
 </Stage>;};

// Re-skinned exact OutroGroupPhotoLaunch flight/crane/stamp grammar.
const els=[
 {file:'glass-skins',x:960,y:183,w:760,h:100,dx:0,dy:-360,rot:-2,cue:4},{file:'glass-mini',x:320,y:360,w:500,h:231,dx:-500,dy:0,rot:-5,cue:7},
 {file:'dark-mini',x:1610,y:350,w:500,h:231,dx:500,dy:0,rot:4,cue:10},{file:'advanced-background',x:1620,y:800,w:380,h:394,dx:450,dy:260,rot:-3,cue:13},
 {file:'advanced-reading',x:310,y:810,w:400,h:280,dx:-400,dy:300,rot:3,cue:16},{file:'sidebar-builder',x:960,y:918,w:720,h:200,dx:0,dy:320,rot:2,cue:19},
 {file:'glass-modes',x:950,y:150,w:500,h:223,dx:0,dy:-240,rot:-1.5,cue:22},{file:'saved-profiles',x:1610,y:165,w:500,h:120,dx:380,dy:0,rot:-2,cue:25},
 {file:'glass-save',x:320,y:150,w:460,h:39,dx:-360,dy:-200,rot:2.5,cue:28}];
export const Outro:React.FC<Props>=({title='Filamizer',accent=ACC,duration=180})=>{const f=useCurrentFrame(),crane=ramp(f,0,40,Easing.bezier(.3,0,.2,1)),recede=ramp(f,42,8);return <Stage accent={accent}>
 <AbsoluteFill style={{transform:`perspective(1400px) rotateX(${4*(1-crane)}deg) scale(${1.06-.06*crane+.02*ramp(f,36,60,Easing.inOut(Easing.cubic))})`}}>{els.map(el=>{const t=ramp(f,el.cue,12,flight),land=f<el.cue+9?0:f<el.cue+12?(f-el.cue-9)/3:1-clamp((f-el.cue-12)/4);return <div key={el.file} style={{position:'absolute',left:el.x-el.w/2,top:el.y-el.h/2,width:el.w,height:el.h,overflow:'hidden',borderRadius:16,opacity:ramp(f,el.cue,3)*(.42-.12*recede),transform:`translate(${el.dx*(1-t)}px,${el.dy*(1-t)}px) rotate(${el.rot*(2-t)}deg) scale(${(1.12-.12*t)*(1-.015*land)})`,boxShadow:'0 18px 46px #0009',filter:recede>.9?'blur(1px) saturate(.65)':undefined}}><Texture name={el.file}/></div>;})}</AbsoluteFill>
 <AbsoluteFill style={{background:'radial-gradient(ellipse 710px 430px at 50% 50%,#030712 5%,#030712d9 50%,transparent)',opacity:ramp(f,34,18)}}/>
 {Array.from({length:20},(_,i)=><div key={i} style={{position:'absolute',left:(i*439+137)%1920+Math.sin(Math.min(f,140)*.022+i)*9,top:((i*613+271)-Math.min(f,140)*(.3+(i%5)*.11)+1080)%1080,width:2.4+(i%3)*.9,height:2.4+(i%3)*.9,borderRadius:10,background:accent,opacity:.25,boxShadow:`0 0 9px ${accent}`}}/>)}
 <div style={{position:'absolute',left:0,right:0,top:365,textAlign:'center'}}><div style={{fontSize:146,fontWeight:740,letterSpacing:-6,display:'flex',justifyContent:'center'}}>{title.split('').map((ch,i)=>{const t=ramp(f,42+i*1.8,8,Easing.bezier(.2,.75,.3,1));return <span key={i} style={{display:'inline-block',opacity:t,transform:`translateY(${(1-t)*28}px) scale(${1.35-.35*t})`,filter:`blur(${(1-t)*8}px)`}}>{ch}</span>;})}</div><div style={{height:5,width:260,margin:'25px auto',background:accent,scale:`${ramp(f,58,12)} 1`}}/><div style={{fontSize:57,lineHeight:1.15,letterSpacing:-1.5,opacity:ramp(f,68,20)}}>Make your panel feel like yours.</div><div style={{fontSize:34,color:MUTED,marginTop:33,opacity:ramp(f,92,20)}}>Independent Filament 5 plugin · Pre-release</div></div>
 <AbsoluteFill style={{background:INK,zIndex:1000,opacity:ramp(f,duration-8,7,Easing.in(Easing.quad))}}/>
 </Stage>;};

export const Film:React.FC<{sound?:boolean}>=({sound=true})=><AbsoluteFill style={{background:INK}}>
 <Sequence name="01 · Filamizer" from={SHOTS.hero.from} durationInFrames={SHOTS.hero.duration}><Hero/></Sequence>
 <Sequence name="02 · Install and authorize" from={SHOTS.setup.from} durationInFrames={SHOTS.setup.duration}><Setup/></Sequence>
 <Sequence name="03 · Choose a skin" from={SHOTS.skins.from} durationInFrames={SHOTS.skins.duration}><Skins/></Sequence>
 <Sequence name="04 · Fine-tune" from={SHOTS.tune.from} durationInFrames={SHOTS.tune.duration}><Tune/></Sequence>
 <Sequence name="05 · Light and dark" from={SHOTS.modes.from} durationInFrames={SHOTS.modes.duration}><Modes/></Sequence>
 <Sequence name="06 · Sidebar" from={SHOTS.sidebar.from} durationInFrames={SHOTS.sidebar.duration}><Sidebar/></Sequence>
 <Sequence name="07 · Save and personalize" from={SHOTS.save.from} durationInFrames={SHOTS.save.duration}><Save/></Sequence>
 <Sequence name="08 · Finale" from={SHOTS.outro.from} durationInFrames={SHOTS.outro.duration}><Outro/></Sequence>
 {sound?SFX.map((s,i)=><Sequence key={i} name={s.action} from={s.from} durationInFrames={Math.min(s.duration,TOTAL-s.from)}><Audio src={staticFile(s.src)} volume={s.volume}/></Sequence>):null}
</AbsoluteFill>;

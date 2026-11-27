/* Filamizer: deterministic motion design, original audio, no external services. */
const { createCanvas, GlobalFonts } = require('@napi-rs/canvas');
const ffmpeg = require('ffmpeg-static');
const { spawn } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const { once } = require('node:events');
const ROOT = __dirname;
GlobalFonts.registerFromPath('C:/Windows/Fonts/segoeui.ttf', 'UI');
GlobalFonts.registerFromPath('C:/Windows/Fonts/segoeuib.ttf', 'UIBold');
GlobalFonts.registerFromPath('C:/Windows/Fonts/segoeuil.ttf', 'UILight');
const FPS = 30, DURATION = 56;
const WHITE = '#f2f2ff', MUTED = '#a9aac7', LILAC = '#a99aff', CYAN = '#73e7ee';
const scenes = [
  { start: 0, end: 6, tag: 'MEET FILAMIZER', title: ['Your dashboard.', 'Your style.'], sub: 'Runtime customization for Filament 5.' },
  { start: 6, end: 14, tag: '01 / FIVE DISTINCT SKINS', title: ['One panel.', 'Five personalities.'], sub: 'Classic · Liquid Glass · Neon · Soft · Minimal' },
  { start: 14, end: 22, tag: '02 / LIGHT & DARK', title: ['Two modes.', 'Your rules.'], sub: 'Design light and dark looks independently.' },
  { start: 22, end: 30, tag: '03 / BACKGROUNDS IN MOTION', title: ['Set the', 'atmosphere.'], sub: 'Gradients, mesh, animated aurora and images.' },
  { start: 30, end: 38, tag: '04 / THE FINER DETAILS', title: ['Make every', 'detail yours.'], sub: 'Colors. Fonts. Spacing. Corners.' },
  { start: 38, end: 45, tag: '05 / YOUR NAVIGATION', title: ['A sidebar.', 'Your way.'], sub: 'Reorder, group and refine your navigation.' },
  { start: 45, end: 50, tag: '06 / SAVED PROFILES', title: ['Find your look.', 'Keep it.'], sub: 'Shared defaults. Personal appearance.' },
  { start: 50, end: 56, tag: 'FILAMIZER / FOR FILAMENT 5', title: ['Your dashboard.', 'Your style.'], sub: 'Customize at runtime. No custom theme build.' },
];
const clamp = x => Math.max(0, Math.min(1, x));
const ease = x => 1 - Math.pow(1 - clamp(x), 3);
const smooth = x => { x = clamp(x); return x * x * (3 - 2 * x); };
const mix = (a, b, p) => a + (b - a) * p;
function rr(c,x,y,w,h,r=16,fill,stroke) {
  c.beginPath(); c.roundRect(x,y,w,h,r);
  if(fill) { c.fillStyle=fill; c.fill(); }
  if(stroke) { c.strokeStyle=stroke; c.lineWidth=1; c.stroke(); }
}
function txt(c,str,x,y,size=24,color=WHITE,font='UI',align='left') {
  c.font=`${size}px ${font}`; c.fillStyle=color; c.textAlign=align; c.textBaseline='alphabetic'; c.fillText(str,x,y);
}
function line(c,x1,y1,x2,y2,color,width=1) {
  c.strokeStyle=color; c.lineWidth=width; c.beginPath(); c.moveTo(x1,y1); c.lineTo(x2,y2); c.stroke();
}
function dot(c,x,y,r,color) { c.fillStyle=color; c.beginPath(); c.arc(x,y,r,0,Math.PI*2); c.fill(); }
function check(c,x,y,color=CYAN) {line(c,x-8,y,x-2,y+6,color,3);line(c,x-2,y+6,x+10,y-8,color,3);}
function pill(c,label,x,y,accent=LILAC,w) {
  c.font='19px UI'; w=w||c.measureText(label).width+42;
  rr(c,x,y,w,44,22,'#17152d','#3b3658'); dot(c,x+19,y+22,3,accent); txt(c,label,x+31,y+28,18,WHITE); return w;
}
function logo(c,x,y,s=1) {
  c.save(); c.translate(x,y); c.scale(s,s);
  const g=c.createLinearGradient(0,0,50,50); g.addColorStop(0,'#b6a0ff');g.addColorStop(1,'#6053ef');
  rr(c,0,0,48,48,13,g); c.lineCap='round'; line(c,15,16,34,16,'#fff',4); line(c,15,25,29,25,'#fff',4);line(c,15,16,15,34,'#fff',4);
  c.restore();
}
function glow(c,x,y,r,color) {
  const g=c.createRadialGradient(x,y,0,x,y,r); g.addColorStop(0,color);g.addColorStop(1,'transparent');c.fillStyle=g;c.fillRect(x-r,y-r,r*2,r*2);
}
const panelCache=new Map();
function dashboard({skin='glass',dark=true,accent='#9584ff',backdrop='aurora',compact=false}={}) {
  const key=JSON.stringify({skin,dark,accent,backdrop,compact}); if(panelCache.has(key))return panelCache.get(key);
  const a=createCanvas(1100,700),c=a.getContext('2d');
  const fg=dark?'#eeeefa':'#26233f',muted=dark?'#a6abc5':'#77768a';
  const surf=dark?'#171a2e':'#ffffff',border=dark?'#3a3a59':'#e0deeb';
  c.save();rr(c,0,0,1100,700,24);c.clip();c.fillStyle=dark?'#0e1020':'#f0f0fa';c.fillRect(0,0,1100,700);
  if(skin==='glass' || backdrop==='mesh') {glow(c,330,180,620,dark?'#39326d':'#dcd3ff');glow(c,1000,570,600,dark?'#165d68':'#b9e9ed');}
  function surface(x,y,w,h,r=16) {
    c.save();
    if(skin==='neon'){c.shadowColor=accent;c.shadowBlur=13;}
    if(skin==='soft'){c.shadowColor=dark?'#0008':'#8279ad33';c.shadowBlur=22;c.shadowOffsetY=9;}
    rr(c,x,y,w,h,skin==='minimal'?5:r,skin==='glass'?(dark?'#ffffff0d':'#ffffffa9'):surf,skin==='neon'?accent+'88':skin==='glass'?'#ffffff30':border);
    c.restore();
  }
  surface(13,13,1074,58,15); logo(c,30,25,.65);txt(c,'Filamizer',75,51,22,fg,'UIBold');
  rr(c,490,28,345,28,7,dark?'#ffffff0c':'#f4f3f8'); txt(c,'Search your panel',507,48,13,muted);
  dot(c,1002,42,12,accent);txt(c,'F',1002,47,12,'#fff','UIBold','center');
  const sw=compact?70:196;surface(13,83,sw,604,15);
  const nav=['Dashboard','Customers','Projects','Settings'];
  nav.forEach((n,i)=>{const y=106+i*57;if(!i)rr(c,24,y,sw-22,43,8,accent+'28');
    rr(c,37,y+13,16,16,4,null,i?muted:accent);if(!compact)txt(c,n,68,y+28,16,i?fg:accent,i?'UI':'UIBold');});
  if(!compact) {txt(c,'APPEARANCE',33,485,11,muted);txt(c,'Personalize your workspace',33,515,11,muted);}
  const mx=sw+36, cw=1061-mx;
  txt(c,'Dashboard',mx,125,28,fg,'UIBold');txt(c,'A workspace that feels like you.',mx,152,15,muted);
  const stats=[['Customers','128','+12.8%'],['Projects','42','+8.2%'],['Active tasks','24','+16.4%']];
  stats.forEach(([label,val,delta],i)=>{const w=(cw-32)/3,x=mx+i*(w+16);surface(x,178,w,122);txt(c,label,x+19,208,15,muted);txt(c,val,x+19,254,35,fg,'UIBold');txt(c,delta,x+w-18,255,13,dark?'#6bdabb':'#198861','UI','right');line(c,x+20,279,x+w-20,279,accent,3);});
  surface(mx,317,cw*.64-8,216);txt(c,'Activity overview',mx+20,352,17,fg,'UIBold');
  for(let i=0;i<4;i++)line(c,mx+20,382+i*35,mx+cw*.64-28,382+i*35,dark?'#ffffff0b':'#0000000a');
  c.beginPath();for(let i=0;i<45;i++){let x=mx+20+i*(cw*.64-48)/44,y=475-Math.sin(i*.26)*14-i*1.8+Math.cos(i*.67)*8;if(i===0)c.moveTo(x,y);else c.lineTo(x,y);}c.strokeStyle=accent;c.lineWidth=3;c.stroke();
  const xx=mx+cw*.64+8;surface(xx,317,cw*.36-8,216);txt(c,'Your workspace',xx+20,352,17,fg,'UIBold');
  c.lineWidth=15;c.strokeStyle=dark?'#ffffff12':'#eceaf6';c.beginPath();c.arc(xx+(cw*.36-8)/2,429,47,0,Math.PI*2);c.stroke();c.strokeStyle=accent;c.beginPath();c.arc(xx+(cw*.36-8)/2,429,47,-Math.PI/2,Math.PI*.95);c.stroke();txt(c,'76%',xx+(cw*.36-8)/2,437,23,fg,'UIBold','center');
  surface(mx,550,cw,122);txt(c,'Recent projects',mx+20,581,17,fg,'UIBold');
  ['Website refresh','Product launch'].forEach((n,i)=>{let yy=611+i*32;dot(c,mx+25,yy-4,4,i?CYAN:accent);txt(c,n,mx+39,yy,14,fg);txt(c,i?'In progress':'Completed',mx+cw-22,yy,12,muted,'UI','right');});
  c.restore();rr(c,.7,.7,1098.6,698.6,24,null,dark?'#ffffff43':'#ffffffaa');panelCache.set(key,a);return a;
}
function place(c,img,x,y,w,h,angle=0,alpha=1) {
  c.save();c.globalAlpha*=alpha;c.translate(x+w/2,y+h/2);c.rotate(angle);c.drawImage(img,-w/2,-h/2,w,h);c.restore();
}
function floatPanel(c,x,y,w,h,draw) {rr(c,x,y,w,h,22,'#15152beF','#514b7377');draw(x,y,w,h);}
function drawSwatches(c,x,y,p,vertical=false) {
  const colors=['#9380ff','#4ab6ff','#5ce0b3','#f497b8','#f9bb75'];
  colors.forEach((col,i)=>{const xx=x+(vertical?0:i*58),yy=y+(vertical?i*58:0);dot(c,xx,yy,19,col);if(i===Math.floor(p*4.99)) {c.lineWidth=2;c.strokeStyle='#fff';c.beginPath();c.arc(xx,yy,25,0,Math.PI*2);c.stroke();}});
}
function controls(c,x,y,w,t) {
  floatPanel(c,x,y,w,230,(x,y,w)=>{txt(c,'Fine-tune your look',x+24,y+39,21,WHITE,'UIBold');
    const labels=['Backdrop intensity','Corner radius','Layout density'];labels.forEach((v,i)=>{const yy=y+76+i*55;txt(c,v,x+24,yy,15,MUTED);const ww=w-48;rr(c,x+24,yy+14,ww,4,2,'#ffffff18');const p=.35+.25*Math.sin(t*.8+i);rr(c,x+24,yy+14,ww*p,4,2,LILAC);dot(c,x+24+ww*p,yy+16,6,WHITE);});});
}
function visual(c,idx,t,p,v) {
  const W=v?1080:1920,H=v?1920:1080;
  let x=v?65:825,y=v?720:230,w=v?950:1020,h=w*700/1100;
  const reveal=ease(t/1.1);y+=(1-reveal)*70;
  y+=Math.sin(t*.75)*5;
  c.save();c.globalAlpha*=reveal;
  if(idx===0) {
    if(v){place(c,dashboard({skin:'classic',dark:false}),-160,990,840,535,-.12,.42);place(c,dashboard({}),100,760,1000,636,.07);pill(c,'No custom theme build',280,1480,CYAN);}
    else {place(c,dashboard({skin:'classic',dark:false}),960,170,810,515,.08,.46);place(c,dashboard({}),800,320,1030,655,-.055);pill(c,'Customize at runtime',1230,935,CYAN);}
  } else if(idx===1) {
    const names=['Classic','Liquid Glass','Neon','Soft','Minimal'], keys=['classic','glass','neon','soft','minimal'];
    const k=Math.min(4,Math.floor(Math.max(0,t-1)/1.25));
    place(c,dashboard({skin:keys[k],dark:k!==3,accent:k===2?'#84e7f4':'#9584ff'}),x,y,w,h,Math.sin(t*.6)*.012);
    const py=v?y+h+65:875, pw=v?178:183;
    names.forEach((n,i)=>{let xx=(v?57:825)+i*(pw+10);rr(c,xx,py,pw,65,16,i===k?'#8f7af2':'#19172e',i===k?'#d3c8ff':'#3b3658');txt(c,n,xx+pw/2,py+39,v?20:19,i===k?'#fff':MUTED,i===k?'UIBold':'UI','center');});
    pill(c,'5 distinct skins',v?370:1220,v?1540:960);
  } else if(idx===2) {
    const light=dashboard({skin:'classic',dark:false,accent:'#6652d7'}),dark=dashboard({skin:'glass',dark:true,accent:'#81d9e9'});
    // A moving split reveals truly separate palette illustrations.
    place(c,light,x,y,w,h);const cut=x+w*(.5+.22*Math.sin(t*.75));c.save();c.beginPath();c.rect(cut,y,x+w-cut,h);c.clip();place(c,dark,x,y,w,h);c.restore();line(c,cut,y,cut,y+h,'#c7baff',2);dot(c,cut,y+h*.5,17,'#c4b4ff');
    pill(c,'Light',x+35,y+h+43,LILAC,125);pill(c,'Dark',x+w-160,y+h+43,CYAN,125);
    if(v){txt(c,'Different palettes. Different skins.',540,1585,28,MUTED,'UI','center');}
  } else if(idx===3) {
    // Animated aurora ribbons behind the transparent panel illustration.
    c.save();rr(c,x-30,y-50,w+60,h+110,50);c.clip();
    for(let i=0;i<4;i++){c.save();c.translate(x+w*.5,y+h*.5);c.rotate(-.42+Math.sin(t*.3+i)*.14);const g=c.createLinearGradient(-w,0,w,0);g.addColorStop(0,'transparent');g.addColorStop(.3,['#7058c455','#208d9e66','#a87ad666','#40d9c744'][i]);g.addColorStop(.6,['#5781fa66','#b16fce66','#36b5a466','#8d5fe766'][i]);g.addColorStop(1,'transparent');rr(c,-w,-170+i*90+Math.sin(t*.5+i)*65,w*2,180,90,g);c.restore();}c.restore();
    place(c,dashboard({skin:'glass'}),x+20,y+20,w-40,h-25,0,.9);
    const labels=['Gradient','Mesh','Aurora','Image'];labels.forEach((l,i)=>pill(c,l,x+i*(v?233:245),y+h+75,i===2?CYAN:LILAC,v?213:224));
    pill(c,'Aurora · animated',v?350:x+55,v?1570:y+45,CYAN);
  } else if(idx===4) {
    const colors=['#9380ff','#4ab6ff','#5ce0b3','#f497b8','#f9bb75'];const accent=colors[Math.min(4,Math.floor(t/1.5))];
    place(c,dashboard({accent}),x,y,w,h);
    const cx=v?108:1005,cy=v?y+h-30:720;floatPanel(c,cx,cy,v?864:750,150,(x,y,w)=>{txt(c,'Your palette',x+26,y+38,18,WHITE,'UIBold');drawSwatches(c,x+47,y+94,t/8);txt(c,'Aa',x+w-119,y+92,53,WHITE,'UILight');txt(c,'Latin + Arabic',x+w-143,y+126,15,MUTED);});
    if(v){controls(c,108,1555,864,t);}else {pill(c,'Typography',945,940);pill(c,'Density',1150,940);pill(c,'Radius',1330,940);}
  } else if(idx===5) {
    place(c,dashboard({compact:t>3.5}),x,y,w,h);
    const xx=v?150:1020,yy=v?y+95:y+55,ww=v?780:620;
    floatPanel(c,xx,yy,ww,430,(x,y,w)=>{txt(c,'Sidebar layout',x+30,y+46,25,WHITE,'UIBold');txt(c,'Drag to reorder your workspace',x+30,y+76,16,MUTED);
      const shift=smooth((t-1.6)/1.2),order=['Dashboard','Customers','Projects','Settings'];
      order.forEach((n,i)=>{let pos=i;if(i===1)pos=mix(1,2,shift);if(i===2)pos=mix(2,1,shift);let ry=y+108+pos*67;rr(c,x+24,ry,w-48,55,12,i===2?'#3b305c':'#ffffff07',i===2?'#9280ce':'#ffffff15');for(let row=0;row<3;row++){dot(c,x+43,ry+19+row*7,1.5,MUTED);dot(c,x+50,ry+19+row*7,1.5,MUTED);}txt(c,n,x+82,ry+35,19,WHITE);if(i===2){line(c,x+w-53,ry+18,x+w-53,ry+37,LILAC,2);line(c,x+w-58,ry+23,x+w-53,ry+18,LILAC,2);line(c,x+w-48,ry+32,x+w-53,ry+37,LILAC,2);}});
    });
    pill(c,'Classic',x+25,y+h+55);pill(c,'Floating',x+(v?285:330),y+h+55);pill(c,'Collapsible',x+(v?555:635),y+h+55,CYAN);
  } else if(idx===6) {
    const yy=v?750:265,xx=v?105:890,ww=v?870:880;
    ['Royal Indigo','Focus mode','My workspace'].forEach((n,i)=>{let sy=yy+i*173;rr(c,xx+Math.sin(t*.5+i)*6,sy,ww,145,23,i===2?'#2b2348':'#17182c',i===2?'#9c86ed':'#ffffff25');
      place(c,dashboard({dark:i!==0,accent:['#9584ff','#71d5d9','#e9a7ce'][i]}),xx+20,sy+20,164,104);txt(c,n,xx+210,sy+61,27,WHITE,'UIBold');txt(c,i===0?'Shared profile':'Personal profile',xx+210,sy+98,18,MUTED);if(i===2)check(c,xx+ww-48,sy+77);else {line(c,xx+ww-55,sy+84,xx+ww-42,sy+71,MUTED,2);line(c,xx+ww-50,sy+71,xx+ww-42,sy+71,MUTED,2);line(c,xx+ww-42,sy+71,xx+ww-42,sy+79,MUTED,2);}});
    pill(c,'Save · reuse · make it yours',v?290:1110,yy+575,CYAN);
  } else {
    if(v){logo(c,448,785,3.8);txt(c,'Filamizer',540,1115,117,WHITE,'UIBold','center');txt(c,'FOR FILAMENT 5',540,1190,26,LILAC,'UI','center');pill(c,'Make it yours',414,1310,CYAN);}
    else {logo(c,1195,300,3.4);txt(c,'Filamizer',1350,625,116,WHITE,'UIBold','center');txt(c,'FOR FILAMENT 5',1350,690,25,LILAC,'UI','center');pill(c,'Make it yours',1230,767,CYAN);}
  }
  c.restore();
}
function renderFrame(canvas,time,vertical) {
  const c=canvas.getContext('2d'),W=canvas.width,H=canvas.height;
  // Logical composition dimensions are independent between formats.
  c.save();c.scale(W/(vertical?1080:1920),H/(vertical?1920:1080));const w=vertical?1080:1920,h=vertical?1920:1080;
  c.fillStyle='#080912';c.fillRect(0,0,w,h);
  glow(c,w*.8+Math.sin(time*.12)*70,h*.38,vertical?1000:930,'#3020524c');
  glow(c,w*.25,h*.96,vertical?700:800,'#073b4544');
  c.strokeStyle='#ffffff04';c.lineWidth=1;
  for(let j=0;j<18;j++){let xx=j*140+(time*4)%140;line(c,xx,0,xx,h,'#ffffff03');}
  for(let j=0;j<15;j++)line(c,0,j*140,w,j*140,'#ffffff03');
  const idx=scenes.findIndex(s=>time>=s.start&&time<s.end),s=scenes[idx<0?7:idx],i=idx<0?7:idx,t=time-s.start,p=t/(s.end-s.start);
  // Brand bar and scene markers stay steady through transitions.
  logo(c,vertical?74:88,vertical?105:62,.85);txt(c,'Filamizer',vertical?131:145,vertical?137:94,27,WHITE,'UIBold');
  txt(c,'FOR FILAMENT 5',vertical?1006:1832,vertical?136:92,16,MUTED,'UI','right');
  const fade=Math.min(ease(t/.65),clamp((s.end-time)/.32));c.globalAlpha=fade;
  const xx=vertical?76:88,yy=vertical?280:290;
  txt(c,s.tag,xx,yy,vertical?21:18,LILAC,'UIBold');
  const enter=ease(t/.9);let titleSize=vertical?86:90;
  while(titleSize>60){c.font=`${titleSize}px UIBold`;if(Math.max(...s.title.map(l=>c.measureText(l).width))<(vertical?928:690))break;titleSize--;}
  s.title.forEach((l,k)=>txt(c,l,xx,yy+111+k*105+(1-enter)*28,titleSize,k===1?LILAC:WHITE,'UIBold'));
  // Explicit line breaks keep mobile copy inside a 928px safe width.
  const subLines=vertical?[
    ['Runtime customization','for Filament 5.'],
    ['Classic · Liquid Glass · Neon','Soft · Minimal'],
    ['Design light and dark looks','independently.'],
    ['Gradients, mesh, animated aurora','and image backgrounds.'],
    ['Colors. Fonts. Spacing. Corners.'],
    ['Reorder, group and refine','your navigation.'],
    ['Shared defaults.','Personal appearance.'],
    ['Customize at runtime.','No custom theme build.'],
  ][i]:[s.sub];
  subLines.forEach((l,k)=>txt(c,l,xx,yy+291+k*42,vertical?29:25,MUTED));
  if(!vertical&&i===0){pill(c,'5 skins',90,720);pill(c,'Light & dark',250,720,CYAN);pill(c,'Saved profiles',90,785);}
  if(!vertical&&i===7) {pill(c,'Skins',90,725);pill(c,'Colors',235,725);pill(c,'Profiles',390,725,CYAN);}
  visual(c,i,t,p,vertical);
  c.globalAlpha=1;
  const fy=vertical?1810:1003;
  txt(c,'APPEARANCE, REIMAGINED.',vertical?76:88,fy,15,'#777a96');
  txt(c,`${String(i+1).padStart(2,'0')} / 08`,vertical?1006:1832,fy,16,MUTED,'UI','right');
  for(let j=0;j<8;j++){let bx=(vertical?76:88)+j*(vertical?117:219),bw=vertical?103:201;rr(c,bx,fy+26,bw,3,1,'#ffffff15');if(j<i)rr(c,bx,fy+26,bw,3,1,LILAC);if(j===i)rr(c,bx,fy+26,bw*p,3,1,LILAC);}
  // Short global fade makes the first and last frames clean.
  const black=1-Math.min(clamp(time/.4),clamp((DURATION-time)/.6));if(black>0){c.fillStyle=`rgba(8,9,18,${black})`;c.fillRect(0,0,w,h);}
  c.restore();
}
function soundtrack() {
  const out=path.join(ROOT,'soundtrack.wav');if(fs.existsSync(out))return out;
  const sr=44100,n=sr*DURATION,buf=Buffer.alloc(44+n*4);
  buf.write('RIFF');buf.writeUInt32LE(36+n*4,4);buf.write('WAVEfmt ',8);buf.writeUInt32LE(16,16);buf.writeUInt16LE(1,20);buf.writeUInt16LE(2,22);buf.writeUInt32LE(sr,24);buf.writeUInt32LE(sr*4,28);buf.writeUInt16LE(4,32);buf.writeUInt16LE(16,34);buf.write('data',36);buf.writeUInt32LE(n*4,40);
  const note=m=>440*Math.pow(2,(m-69)/12),chords=[[45,52,57,60,64],[41,48,53,57,60],[48,55,60,64,67],[43,50,55,59,62]];
  let seed=91;const noise=()=>{seed=(Math.imul(seed,1664525)+1013904223)|0;return seed/2147483648;};
  let prev=0;
  for(let j=0;j<n;j++) {
    const t=j/sr,beat=t*2,bp=(t%.5),bar=Math.floor(t/4)%4,ch=chords[bar];
    const fade=Math.min(clamp(t/2),clamp((DURATION-t)/2.4)),duck=1-.48*Math.exp(-bp*14);
    let pad=0;for(let k=1;k<5;k++){let f=note(ch[k]);pad+=Math.sin(t*f*2*Math.PI+.1*Math.sin(t*.7))*Math.sin(Math.PI*(t%4)/4)*.023;}
    const kick= Math.sin(2*Math.PI*(47*bp+8*(1-Math.exp(-bp*38))))*Math.exp(-bp*17)*.35;
    let hh=(t%.25),hat=noise()*Math.exp(-hh*95)*.032;
    const snare=(Math.floor(beat)%2===1)?noise()*Math.exp(-bp*28)*.065:0;
    let ap=t%.25,an=ch[1+Math.floor(t*4)%4]+12,af=note(an);
    const arp=(Math.sin(2*Math.PI*af*t)+.24*Math.sin(2*Math.PI*af*2*t))*Math.exp(-ap*15)*Math.min(1,ap*300)*.052;
    const bass=Math.sin(2*Math.PI*note(ch[0]-12)*t)*.11*duck;
    // Two soft stereo taps give the sparse arpeggio some space.
    const left=(kick+snare+hat+bass+pad*duck+arp+prev*.15)*fade;
    const right=(kick+snare-hat*.7+bass+pad*.95*duck+arp*.78+prev*.22)*fade;
    prev=arp;
    buf.writeInt16LE(Math.round(Math.tanh(left*1.5)*24500),44+j*4);buf.writeInt16LE(Math.round(Math.tanh(right*1.5)*24500),46+j*4);
  }
  fs.writeFileSync(out,buf);return out;
}
async function render(format) {
  const vertical=format==='vertical',w=vertical?1080:1920,h=vertical?1920:1080;
  const canvas=createCanvas(w,h),audio=soundtrack(),output=path.join(ROOT,'exports',`filamizer-${format}-1080p.mp4`);
  fs.mkdirSync(path.dirname(output),{recursive:true});
  const args=['-y','-hide_banner','-loglevel','warning','-f','rawvideo','-pixel_format','rgba','-video_size',`${w}x${h}`,'-framerate',String(FPS),'-i','pipe:0','-i',audio,'-c:v','libx264','-preset','fast','-crf','18','-pix_fmt','yuv420p','-c:a','aac','-b:a','192k','-af','alimiter=limit=0.9','-movflags','+faststart','-shortest',output];
  const proc=spawn(ffmpeg,args,{stdio:['pipe','ignore','inherit'],windowsHide:true});
  let failed;proc.stdin.on('error',e=>{failed=e;});
  const done=once(proc,'close');
  for(let f=0;f<DURATION*FPS;f++) {
    if(failed)throw failed;
    renderFrame(canvas,f/FPS,vertical);
    if(!proc.stdin.write(canvas.data()))await once(proc.stdin,'drain');
    if(f%(FPS*4)===0)console.log(`${format}: ${Math.round(f/(DURATION*FPS)*100)}% (${f/FPS}s)`);
  }
  proc.stdin.end();const [code]=await done;if(code!==0)throw new Error(`ffmpeg exited ${code}`);
  console.log(`Saved ${output}`);
}
function previews() {
  fs.mkdirSync(path.join(ROOT,'previews'),{recursive:true});
  for(const vertical of [false,true]) {
    const w=vertical?540:960,h=vertical?960:540,c=createCanvas(w,h);
    const sheet=createCanvas(w*4,h*2),sc=sheet.getContext('2d');
    scenes.forEach((s,i)=>{renderFrame(c,s.start+Math.min(3,(s.end-s.start)/2),vertical);sc.drawImage(c,(i%4)*w,Math.floor(i/4)*h);});
    fs.writeFileSync(path.join(ROOT,'previews',`${vertical?'vertical':'landscape'}-storyboard.jpg`),sheet.toBuffer('image/jpeg',90));
    const poster=createCanvas(vertical?1080:1920,vertical?1920:1080);renderFrame(poster,2.8,vertical);fs.writeFileSync(path.join(ROOT,'exports',`filamizer-${vertical?'vertical':'landscape'}-poster.jpg`),poster.toBuffer('image/jpeg',95));
  }
}
(async()=>{fs.mkdirSync(path.join(ROOT,'exports'),{recursive:true});const arg=process.argv[2]||'all';if(arg==='previews'){previews();return;}if(arg==='all'||arg==='landscape')await render('landscape');if(arg==='all'||arg==='vertical')await render('vertical');previews();})().catch(e=>{console.error(e);process.exitCode=1;});

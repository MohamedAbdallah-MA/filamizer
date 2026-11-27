import {Film,Hero,Setup,Skins,Tune,Modes,Sidebar,Save,Outro} from './Film';
import {SHOTS,SFX,TOTAL} from './timeline';
const schema=[{type:'textarea',key:'title',label:'Title',default:''},{type:'color',key:'accent',label:'Accent color',default:'#a5b4fc'}];
export const WORKBENCH={name:'Filamizer · cinematic tutorial',fps:30,width:1920,height:1080,total:TOTAL,background:'#030712',revision:'1',
shots:[
{id:'hero',...SHOTS.hero,component:Hero,props:{title:'Your panel.\nYour signature.',accent:'#a5b4fc'},schema},
{id:'setup',...SHOTS.setup,component:Setup,props:{title:'A few lines. A new look.',accent:'#a5b4fc'},schema},
{id:'skins',...SHOTS.skins,component:Skins,props:{title:'Five skins. One click.',accent:'#a5b4fc'},schema},
{id:'tune',...SHOTS.tune,component:Tune,props:{title:'Fine-tune the details.',accent:'#a5b4fc'},schema},
{id:'modes',...SHOTS.modes,component:Modes,props:{title:'Light and dark. Independently.',accent:'#a5b4fc'},schema},
{id:'sidebar',...SHOTS.sidebar,component:Sidebar,props:{title:'Give navigation a better flow.',accent:'#a5b4fc'},schema},
{id:'save',...SHOTS.save,component:Save,props:{title:'Save it. Keep it. Make it yours.',accent:'#a5b4fc'},schema},
{id:'outro',...SHOTS.outro,component:Outro,props:{title:'Filamizer',accent:'#a5b4fc',duration:180},schema,durationProp:'duration'}],
transitions:[],captions:[],overlays:[],sfx:SFX.map(s=>({from:s.from,duration:s.duration,src:s.src,volume:s.volume})),bgm:[],original:Film};

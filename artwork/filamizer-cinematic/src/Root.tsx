import {Composition,Folder} from 'remotion';
import {Film,Hero,Setup,Skins,Tune,Modes,Sidebar,Save,Outro} from './Film';
export const RemotionRoot:React.FC=()=> <>
 <Composition id="Filamizer-Cinematic" component={Film} width={1920} height={1080} fps={30} durationInFrames={1800} defaultProps={{sound:true}}/>
 <Folder name="Scenes">
  <Composition id="Introduction" component={Hero} width={1920} height={1080} fps={30} durationInFrames={180} defaultProps={{title:'Your panel.\nYour signature.',accent:'#a5b4fc'}}/>
  <Composition id="Setup" component={Setup} width={1920} height={1080} fps={30} durationInFrames={240} defaultProps={{title:'A few lines. A new look.',accent:'#a5b4fc'}}/>
  <Composition id="Skins" component={Skins} width={1920} height={1080} fps={30} durationInFrames={180} defaultProps={{title:'Five skins. One click.',accent:'#a5b4fc'}}/>
  <Composition id="Customization" component={Tune} width={1920} height={1080} fps={30} durationInFrames={300} defaultProps={{title:'Fine-tune the details.',accent:'#a5b4fc'}}/>
  <Composition id="Light-and-Dark" component={Modes} width={1920} height={1080} fps={30} durationInFrames={210} defaultProps={{title:'Light and dark. Independently.',accent:'#a5b4fc'}}/>
  <Composition id="Sidebar" component={Sidebar} width={1920} height={1080} fps={30} durationInFrames={210} defaultProps={{title:'Give navigation a better flow.',accent:'#a5b4fc'}}/>
  <Composition id="Save" component={Save} width={1920} height={1080} fps={30} durationInFrames={300} defaultProps={{title:'Save it. Keep it. Make it yours.',accent:'#a5b4fc'}}/>
  <Composition id="Finale" component={Outro} width={1920} height={1080} fps={30} durationInFrames={180} defaultProps={{title:'Filamizer',accent:'#a5b4fc',duration:180}}/>
 </Folder>
</>;

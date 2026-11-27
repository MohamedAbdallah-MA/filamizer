"""Use the same local SFX and frame schedule as the Remotion composition."""
import json, subprocess, wave, pathlib, numpy as np, sys
root=pathlib.Path(__file__).resolve().parent
ffmpeg=root/'node_modules/ffmpeg-static'/('ffmpeg.exe' if sys.platform=='win32' else 'ffmpeg')
if not ffmpeg.exists(): ffmpeg=root/'tools/ffmpeg.exe'
data=json.loads((root/'out/cues.json').read_text())
rate=48000
def decode(path):
    p=subprocess.run([str(ffmpeg),'-v','error','-i',str(path),'-f','f32le','-ar',str(rate),'-ac','2','pipe:1'],capture_output=True,check=True)
    return np.frombuffer(p.stdout,dtype='<f4').reshape(-1,2).copy()
if '--verify' not in sys.argv:
    mix=np.zeros((round(data['total']/data['fps']*rate),2),np.float64)
    assets={s['src']:decode(root/'public'/s['src']) for s in data['cues']}
    for s in data['cues']:
        start=round(s['from']/data['fps']*rate)
        count=min(round(s['duration']/data['fps']*rate),len(assets[s['src']]),len(mix)-start)
        mix[start:start+count]+=assets[s['src']][:count]*s['volume']
    peak=float(np.max(np.abs(mix)))
    if peak>=.99: raise RuntimeError(f'Mix clips: {peak}')
    with wave.open(str(root/'out/sfx-mix.wav'),'wb') as w:
        w.setnchannels(2);w.setsampwidth(2);w.setframerate(rate);w.writeframes((np.clip(mix,-1,1)*32767).astype('<i2').tobytes())
    report={'sample_rate':rate,'duration_seconds':len(mix)/rate,'peak_dbfs':float(20*np.log10(peak)),'clipped':False,'cues':len(data['cues']),'aural_review':'Not claimed; decoded waveform and technical alignment checked.'}
    (root/'out/audio-qa.json').write_text(json.dumps(report,indent=2))
    print(json.dumps(report,indent=2))
else:
    movie=root/'exports/filamizer-cinematic-60s.mp4'
    stereo=decode(movie)
    sound=stereo.mean(axis=1)
    probes=[]
    for s in [data['cues'][5],data['cues'][8],data['cues'][14]]:
        src=decode(root/'public'/s['src']).mean(axis=1)
        expected=round(s['from']/30*rate);pad=2000
        part=sound[expected-pad:expected+len(src)+pad]
        corr=np.correlate(part,src,mode='valid')
        lag=int(np.argmax(corr))-pad
        probes.append({'action':s['action'],'expected_onset_sample':expected,'decoded_offset_samples':lag,'offset_frames':lag/rate*30})
    report=json.loads((root/'out/audio-qa.json').read_text())
    version=subprocess.run([str(ffmpeg),'-version'],capture_output=True,text=True,check=True).stdout.splitlines()[0]
    report.update({'pipeline':'Remotion 4.0.541 muted H.264 + FFmpeg AAC 48kHz MP4, Windows','ffmpeg_version':version,'probes':probes,'max_alignment_error_frames':max(abs(p['offset_frames']) for p in probes),'decoded_final_stereo_peak_dbfs':float(20*np.log10(np.abs(stereo).max()))})
    report.pop('decoded_final_peak_dbfs',None)
    (root/'out/audio-qa.json').write_text(json.dumps(report,indent=2))
    print(json.dumps(report,indent=2))

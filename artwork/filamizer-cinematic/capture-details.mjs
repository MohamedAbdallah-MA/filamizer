import {chromium} from 'playwright';
import fs from 'node:fs/promises';
const browser=await chromium.launch({channel:'chrome',headless:true});
const context=await browser.newContext({viewport:{width:1920,height:1080},deviceScaleFactor:3});
const page=await context.newPage();
const out=new URL('./public/textures/',import.meta.url);
await page.goto('http://127.0.0.1:8127/demo-login');
await page.evaluate(()=>document.fonts.ready);await page.waitForTimeout(800);
await page.locator('.fi-studio-profile').first().screenshot({path:new URL('profile-card.png',out).pathname.slice(1)});
await page.getByRole('button',{name:'Save as profile',exact:true}).click();await page.waitForTimeout(800);
const component=await page.evaluate(()=>[...document.querySelectorAll('[wire\\:snapshot]')].map(el=>JSON.parse(el.getAttribute('wire:snapshot')).memo).find(m=>m.name.includes('ManageAppearance'))?.id);
console.log('Page component found',Boolean(component));
await page.evaluate(async(id)=>{await window.Livewire.find(id).$refresh();},component);
await page.waitForTimeout(1200);
console.log('Profile name field',await page.getByRole('textbox',{name:'Profile name',exact:false}).count());
console.log((await page.locator('body').innerText()).slice(-900));
if(await page.getByRole('textbox',{name:'Profile name',exact:false}).count()){
 await page.getByRole('textbox',{name:'Profile name',exact:false}).fill('Indigo Studio');
 const modal=page.getByRole('dialog').filter({has:page.getByRole('textbox',{name:'Profile name',exact:false})}).first();
 await modal.screenshot({path:new URL('profile-modal-dialog.png',out).pathname.slice(1)});
}
await page.screenshot({path:new URL('profile-debug.png',out).pathname.slice(1)});
await browser.close();

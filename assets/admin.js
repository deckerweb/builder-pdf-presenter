/* Media selection and accessible local changelog. */
(() => {
  'use strict';
  document.querySelectorAll('.bpp-media').forEach(group => {
    const input = group.querySelector('input');
    const name = group.querySelector('.bpp-media-name');
    group.querySelector('.bpp-select').addEventListener('click', () => {
      if (!window.wp?.media) return;
      const frame = wp.media({ title: group.querySelector('h4').textContent, library: { type: group.dataset.bppType }, multiple: false });
      frame.on('select', () => {
        const file = frame.state().get('selection').first().toJSON();
        input.value = file.id; name.textContent = file.filename || file.title;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });
      frame.open();
    });
    group.querySelector('.bpp-remove').addEventListener('click', event => {
      input.value = '0'; name.textContent = event.currentTarget.dataset.empty;
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });
  const trigger = document.querySelector('[data-bpp-changelog]');
  const dialog = document.querySelector('.bpp-changelog');
  if (trigger && dialog) {
    trigger.addEventListener('click', event => { dialog.hidden = false; try { HTMLDialogElement.prototype.showModal.call(dialog); event.preventDefault(); } catch { dialog.hidden = true; } });
    dialog.addEventListener('click', event => { if (event.target === dialog) HTMLDialogElement.prototype.close.call(dialog); });
    dialog.addEventListener('close', () => { dialog.hidden = true; trigger.focus(); });
  }
})();
/* Document management preserves existing single-document metadata until an explicit collection save. */
(() => {
  document.querySelectorAll('.bpp-document-manager').forEach(node=>{
    const config=JSON.parse(node.dataset.config),input=node.querySelector('input[name="bpp_documents"]');
    new window.BPPManager(node.querySelector('.bpp-manager-root'),config,entries=>{input.value=JSON.stringify(entries);input.dispatchEvent(new Event('input',{bubbles:true}));input.dispatchEvent(new Event('change',{bubbles:true}));});
  });
  document.querySelectorAll('.bpp-reset-group').forEach(button=>button.addEventListener('click',()=>{button.closest('fieldset').querySelectorAll('input,select').forEach(input=>{input.value='';input.dispatchEvent(new Event('change',{bubbles:true}));});}));
  const preview=document.getElementById('bpp-design-preview');
  if(preview){
    const value=key=>document.querySelector(`[name="bpp_settings[global][${key}]"]`)?.value;
    const update=()=>{preview.replaceChildren();const card=document.createElement('div');card.className='bpp-preview-card bpp-preview-'+value('design');const art=document.createElement('div');art.className='bpp-preview-art';art.textContent='PDF';art.style.height='auto';art.style.aspectRatio=({a4:'210 / 297',portrait:'3 / 4',square:'1',landscape:'4 / 3',auto:'3 / 4'})[value('ratio')]||'210 / 297';const info=document.createElement('div');info.className='bpp-preview-info';if(value('show_title')!=='0'){const title=document.createElement('strong');title.textContent='Builder PDF Presenter';info.append(title);}for(const [key,label,secondary] of [['show_read','read_label',false],['show_download','download_label',true]])if(value(key)!=='0'){const btn=document.createElement('span');btn.className='bpp-preview-button'+(secondary?' bpp-preview-secondary':'');btn.textContent=value(label);info.append(btn);}card.append(art,info);preview.append(card);};
    document.querySelectorAll('[name^="bpp_settings[global]"]').forEach(input=>input.addEventListener('input',update));update();
  }
})();

/* The administrator previews one central template, without changing source files. */
(()=>{const root=document.querySelector('.bpp-watermark-settings');if(!root)return;const field=k=>root.querySelector(`[name="bpp_settings[watermark][${k}]"]`),sample=root.querySelector('.bpp-watermark-sample');const update=()=>{sample.hidden=!field('enabled').checked;sample.style.opacity=String(Number(field('opacity').value)/100);sample.style.fontSize=field('size').value+'px';sample.style.justifyContent=({top:'flex-start',bottom:'flex-end',center:'center'})[field('position').value];sample.querySelector('span').textContent=field('text').value;const image=sample.querySelector('img'),id=Number(field('logo').value);image.hidden=!id;if(id&&window.wp?.media)wp.media.attachment(id).fetch().then(()=>{image.src=wp.media.attachment(id).get('url');}).catch(()=>{image.hidden=true;});};root.querySelectorAll('input,select').forEach(n=>{n.addEventListener('input',update);n.addEventListener('change',update);});update();})();
/* One complete form, an explicit dirty state and a retryable save; no field is disabled by navigation. */
(()=>{
 const form=document.getElementById('bpp-settings-form'),cfg=window.bppSettingsUI;if(!form||!cfg)return;
 const bar=form.querySelector('.bpp-savebar'),status=bar.querySelector('[role=status]'),dot=bar.querySelector('.bpp-save-dot'),button=bar.querySelector('button[type=submit]');
 const snapshot=()=>JSON.stringify([...new FormData(form)].filter(([key])=>key.startsWith('bpp_settings[')));
 let saved=snapshot(),saving=false,failed=false;
 const update=()=>{const dirty=snapshot()!==saved;bar.classList.toggle('is-dirty',dirty);bar.classList.toggle('is-saving',saving);bar.classList.toggle('is-error',failed);dot.hidden=!dirty;status.textContent=saving?cfg.saving:failed?cfg.error:dirty?cfg.dirty:cfg.saved;button.disabled=saving;button.setAttribute('aria-busy',String(saving));};
 form.addEventListener('input',()=>{failed=false;update();});form.addEventListener('change',()=>{failed=false;update();});
 window.addEventListener('beforeunload',event=>{if(snapshot()!==saved){event.preventDefault();event.returnValue='';}});
 form.addEventListener('submit',async event=>{event.preventDefault();if(saving)return;const restoreFocus=document.activeElement===button,sent=snapshot(),data=new FormData(form);data.set('action','bpp_save_settings');data.set('nonce',cfg.nonce);saving=true;failed=false;update();try{const response=await fetch(cfg.ajax,{method:'POST',credentials:'same-origin',body:data});const result=await response.json();if(!response.ok||!result.success)throw new Error('save');saved=sent;}catch{failed=true;}finally{saving=false;update();if(restoreFocus&&document.activeElement===document.body)button.focus();}});
 const field=key=>form.querySelector(`[name="bpp_settings[global][${key}]"]`);
 const dependencies=()=>{for(const [dependent,controller] of [['read_label','show_read'],['download_label','show_download']]){const node=field(dependent);if(node)node.closest('.bpp-field').hidden=field(controller)?.value==='0';}const enabled=form.querySelector('[name="bpp_settings[watermark][enabled]"]');form.querySelector('.bpp-watermark-dependent').hidden=!enabled.checked;};
 form.addEventListener('change',dependencies);dependencies();update();
 const links=[...form.querySelectorAll('.bpp-settings-nav a')];if('IntersectionObserver'in window){const observer=new IntersectionObserver(entries=>{for(const entry of entries)if(entry.isIntersecting){links.forEach(a=>a.removeAttribute('aria-current'));links.find(a=>a.hash==='#'+entry.target.id)?.setAttribute('aria-current','location');}},{rootMargin:'-60px 0px -65% 0px'});links.forEach(a=>{const section=document.getElementById(a.hash.slice(1));if(section)observer.observe(section);});}
})();
/* Copy examples without changing the settings form; mapped settings imports stay explicit. */
(() => {
  document.querySelectorAll('.bpp-copy').forEach(button=>button.addEventListener('click',async()=>{
    const old=button.textContent;
    try{await navigator.clipboard.writeText(button.dataset.copy);button.textContent=button.dataset.copied;setTimeout(()=>button.textContent=old,1800);}catch{const code=button.previousElementSibling;const range=document.createRange();range.selectNodeContents(code);const selection=window.getSelection();selection.removeAllRanges();selection.addRange(range);}
  }));
  document.querySelectorAll('.bpp-transfer-form').forEach(form=>{
    const textarea=form.querySelector('textarea'),mapping=form.querySelector('.bpp-import-mapping'),submit=form.querySelector('[type="submit"]'),types=JSON.parse(mapping.dataset.types);
    textarea.addEventListener('input',()=>{
      mapping.replaceChildren();submit.disabled=true;
      try{const data=JSON.parse(textarea.value);if(data.format!=='builder-pdf-presenter-settings'||data.version!==1||!data.settings||typeof data.settings!=='object')return;
        const sources=[...new Set([...(data.settings.enabled||[]),...Object.keys(data.settings.types||{})])];
        for(const source of sources){if(!/^[a-z0-9_-]+$/.test(source))return;const label=document.createElement('label');label.textContent=source+' → ';const select=document.createElement('select');select.name='bpp_mapping['+source+']';select.append(new Option(mapping.dataset.label,''));for(const [key,title]of Object.entries(types))select.append(new Option(title,key));label.append(select);mapping.append(label);}submit.disabled=false;
      }catch{}
    });
  });
})();

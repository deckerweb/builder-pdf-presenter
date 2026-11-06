/* Shared collection editor: WordPress media selection, bounded uploads and explicit list operations. */
(() => {
  'use strict';
  const create = (tag, text, className) => { const e = document.createElement(tag); if (text) e.textContent = text; if (className) e.className = className; return e; };
  window.BPPManager = function(root, config, onChange) {
    const base = window.bppEditor || {}, labels = base.i18n || {};
    let entries = structuredClone(config.entries || []), selected = new Set(), failed = [], dragging;
    const limit = Number(config.limit || 20);
    const inherited = (key, field) => { const value=config.defaults?.[key]; return typeof value === 'boolean' ? (value ? labels.on : labels.off) : (field.choices?.[value] || String(value ?? '')); }; 
    const notice = create('p', '', 'bpp-manager-notice'); notice.setAttribute('role', 'status');
    const toolbar = create('div', '', 'bpp-manager-toolbar');
    const list = create('ol', '', 'bpp-document-list');
    const drop = create('div', labels.drop, 'bpp-dropzone'); drop.tabIndex = 0; drop.setAttribute('role', 'button');
    const count = create('p');
    const button = (parent, text, action, style='button') => { const b=create('button',text,style); b.type='button'; b.addEventListener('click', action); parent.append(b); return b; };
    const status = text => { notice.textContent = text; };
    const remove = keys => { const removed=entries.map((entry,index)=>({entry:structuredClone(entry),index})).filter(row=>keys.has(row.entry.key));if(!removed.length)return;entries=entries.filter(e=>!keys.has(e.key));selected.clear();commit();render();status(labels.removed);button(notice,labels.undo,()=>{for(const row of removed){if(entries.some(e=>e.key===row.entry.key||e.pdf===row.entry.pdf))continue;entries.splice(Math.min(row.index,entries.length),0,row.entry);}commit();render();status('');}); };
    const detailsFor = file => ({filename:file.filename||decodeURIComponent((file.url||file.source_url||'').split('/').pop()),size:file.filesizeInBytes??file.media_details?.filesize??null,media_title:typeof file.title==='object'?file.title.rendered:file.title||''});
    const commit = () => { onChange(entries.map(({key,pdf,title,cover,options})=>({key,pdf,title,cover,options}))); count.textContent=`${entries.length} / ${limit}`; };
    const add = files => {
      for (const file of files) {
        if (entries.some(e=>Number(e.pdf)===Number(file.id))) { status(labels.duplicate); continue; }
        if (entries.length >= limit) { status(labels.limit); break; }
        const rawTitle = typeof file.title === 'object' ? file.title.rendered : (file.title || file.filename || 'PDF');
        entries.push({ ...detailsFor(file), key: crypto.randomUUID(), pdf: Number(file.id), title: '', cover:0, options:{}, suggestion:String(rawTitle).replace(/<[^>]*>/g,'').replace(/\.pdf$/i,'').replace(/[_]+/g,' '), date:file.date || '', available:true, preview:file.sizes?.medium?.url || file.media_details?.sizes?.medium?.source_url || '' });
      }
      commit(); render();
    };
    const selectMedia = (type, callback, multiple=false) => {
      const frame=wp.media({title:labels.add,library:{type},multiple});
      frame.on('select',()=>callback(frame.state().get('selection').toJSON()));frame.open();
    };
    const move = (from,to) => { if(to<0 || to>=entries.length)return; const item=entries.splice(from,1)[0]; entries.splice(to,0,item);commit();render();list.querySelectorAll('li')[to]?.querySelector('input[type="checkbox"]')?.focus(); };
    const preview = async entry => {
      status(labels.busy);
      const body=new URLSearchParams({action:'bpp_preview',_ajax_nonce:base.previewNonce,post_id:String(config.postId),pdf_id:String(entry.pdf)});
      try {const response=await fetch(base.ajax,{method:'POST',credentials:'same-origin',body});const result=await response.json();if(!result.success)throw new Error(result.data?.message || labels.previewMissing);entry.preview=result.data.preview;status('');render();}catch(error){status(error.message || labels.previewMissing);}
    };
    const fields = (parent,entry) => {
      for(const group of Object.values(base.groups || {})) {
        const fs=create('fieldset','', 'bpp-option-group');fs.append(create('legend',group.label));
        for(const key of group.keys) {
          if(key==='reader_watermark'&&!base.watermarkExceptions)continue;
          const field=base.schema[key],wrap=create('label','', 'bpp-field');wrap.append(create('span',field.label));
          let input;
          if(field.type==='text'){input=create('input');input.type='text';input.maxLength=100;input.value=entry.options[key]??'';input.placeholder=String(config.defaults?.[key]??'');}
          else {input=create('select');const choices=field.type==='bool'?{'1':labels.on,'0':labels.off}:field.choices;
            input.append(new Option(labels.inherit,''));for(const [value,title] of Object.entries(choices))input.append(new Option(title,value));
            input.value=entry.options[key]===true?'1':entry.options[key]===false?'0':String(entry.options[key]??'');}
          input.addEventListener('change',()=>{entry.options[key]=input.value;commit();});wrap.append(input);
          wrap.append(create('small',`${labels.source}: ${config.origins?.[key]||labels.inherit} · ${inherited(key,field)}`));fs.append(wrap);
        }
        button(fs,labels.reset,()=>{for(const key of group.keys)delete entry.options[key];commit();render();},'button-link');parent.append(fs);
      }
    };
    const render = () => {
      list.replaceChildren();
      if(!entries.length)list.append(create('li',labels.empty,'bpp-empty'));
      entries.forEach((entry,index)=>{
        const row=create('li','','bpp-document-row');row.draggable=true;row.dataset.key=entry.key;
        row.addEventListener('dragstart',e=>{if(e.target.matches('input,select,button')){e.preventDefault();return;}dragging=index;});
        row.addEventListener('dragover',e=>e.preventDefault());row.addEventListener('drop',e=>{e.preventDefault();e.stopPropagation();if(Number.isInteger(dragging)){move(dragging,index);dragging=undefined;}});
        const head=create('div','','bpp-document-head'),check=create('input');check.type='checkbox';check.checked=selected.has(entry.key);check.setAttribute('aria-label',entry.title||entry.suggestion||'PDF');check.addEventListener('change',()=>check.checked?selected.add(entry.key):selected.delete(entry.key));head.append(check);
        if(entry.preview){const img=create('img');img.src=entry.preview;img.alt='';head.append(img);}else head.append(create('span','PDF','bpp-document-icon'));
        const title=create('label');title.append(create('span',labels.title));const input=create('input');input.type='text';input.maxLength=180;input.value=entry.title||'';input.placeholder=entry.suggestion||'PDF';input.addEventListener('input',()=>{entry.title=input.value;commit();});title.append(input);const bytes=entry.size;title.append(create('small',`${entry.filename||''} · ${bytes===null||bytes===undefined?labels.sizeUnknown:(Number(bytes)/1048576).toLocaleString(undefined,{maximumFractionDigits:2})+' MiB'}`));head.append(title);row.append(head);
        if(entry.available===false)row.append(create('p',labels.missing,'bpp-row-warning'));else if(!entry.preview)row.append(create('p',labels.previewMissing,'bpp-row-warning'));
        const actions=create('div','','bpp-document-actions');
        button(actions,labels.up,()=>move(index,index-1)).disabled=index===0;
        button(actions,labels.down,()=>move(index,index+1)).disabled=index===entries.length-1;
        button(actions,labels.replace,()=>selectMedia('application/pdf',files=>{const file=files[0];if(entries.some(e=>e!==entry&&e.pdf===file.id)){status(labels.duplicate);return;}Object.assign(entry,detailsFor(file));entry.pdf=file.id;entry.available=true;entry.cover=0;entry.preview=file.sizes?.medium?.url||'';entry.suggestion=String(file.title||file.filename||'PDF');commit();render();}));
        button(actions,labels.cover,()=>selectMedia('image',files=>{entry.cover=files[0].id;entry.preview=files[0].sizes?.medium?.url||files[0].url;commit();render();}));
        button(actions,labels.generate,()=>preview(entry));
        button(actions,labels.remove,()=>{remove(new Set([entry.key]));},'button-link-delete');row.append(actions);
        row.append(create('small',labels.replaceHelp,'bpp-replace-help'));if(entry.uses>1)row.append(create('small',`${entry.uses} · ${labels.shared}`, 'bpp-replace-help'));const details=create('details');details.append(create('summary',labels.settings));fields(details,entry);row.append(details);list.append(row);
      });
      count.textContent=`${entries.length} / ${limit}`;
    };
    const upload = async files => {
      failed=[];
      for(const file of files){
        if(entries.length>=limit){failed.push(file);status(labels.limit);continue;}
        if(file.type!=='application/pdf'&&!file.name.toLowerCase().endsWith('.pdf')){failed.push(file);status(labels.uploadError);continue;}
        status(`${labels.busy} ${file.name}`);
        const body=new FormData();body.append('action','upload-attachment');body.append('_wpnonce',base.uploadNonce);body.append('post_id',config.postId||0);body.append('async-upload',file);
        try{const response=await fetch(base.ajax,{method:'POST',credentials:'same-origin',body});const result=await response.json();if(!result.success)throw new Error();add([result.data]);}catch{failed.push(file);}
      }
      status(failed.length?labels.uploadError:'');retry.hidden=!failed.length;
    };
    button(toolbar,labels.add,()=>selectMedia('application/pdf',add,true));
    const sortKey=create('select');sortKey.setAttribute('aria-label',labels.sortApply);
    for(const [value,label] of Object.entries({title:labels.sortTitle,media_title:labels.sortMedia,filename:labels.sortFilename,date:labels.sortDate,size:labels.sortSize}))sortKey.append(new Option(label,value));
    const direction=create('select');direction.setAttribute('aria-label',labels.ascending+' / '+labels.descending);direction.append(new Option(labels.ascending,'ASC'),new Option(labels.descending,'DESC'));toolbar.append(sortKey,direction);
    button(toolbar,labels.sortApply,()=>{const key=sortKey.value,sign=direction.value==='DESC'?-1:1;entries=entries.map((e,i)=>({...e,_position:i})).sort((a,b)=>{if(key==='size'&&(a.size==null||b.size==null))return (a.size==null)-(b.size==null);const av=key==='title'?(a.title||a.suggestion||''):a[key]??'',bv=key==='title'?(b.title||b.suggestion||''):b[key]??'';return sign*(key==='size'?Number(av)-Number(bv):key==='date'?(Date.parse(av)||0)-(Date.parse(bv)||0):String(av).localeCompare(String(bv),undefined,{numeric:true}))||a._position-b._position;});commit();render();});
    button(toolbar,labels.bulkRemove,()=>{remove(selected);});
    button(toolbar,labels.bulkReset,()=>{entries.filter(e=>selected.has(e.key)).forEach(e=>e.options={});commit();render();});
    button(toolbar,labels.bulkPreview,async()=>{for(const entry of entries.filter(e=>!e.preview&&e.available!==false))await preview(entry);});
    const retry=button(toolbar,labels.retry,()=>upload([...failed]));retry.hidden=true;
    drop.addEventListener('click',()=>selectMedia('application/pdf',add,true));drop.addEventListener('keydown',e=>{if(['Enter',' '].includes(e.key)){e.preventDefault();selectMedia('application/pdf',add,true);}});
    drop.addEventListener('dragover',e=>{e.preventDefault();drop.classList.add('is-over');});drop.addEventListener('dragleave',()=>drop.classList.remove('is-over'));drop.addEventListener('drop',e=>{e.preventDefault();drop.classList.remove('is-over');upload([...e.dataTransfer.files]);});
    root.replaceChildren(drop,toolbar,count,notice,list);render();
    return {getEntries:()=>entries};
  };
})();

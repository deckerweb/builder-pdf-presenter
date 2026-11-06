/* Keep direct PDF links usable if a browser cannot initialize the reader module. */
const status=document.getElementById('reader-status');
const timeout=setTimeout(()=>{if(!document.querySelector('#pdf-canvas')?.width||status.textContent===status.dataset.loading)status.textContent=status.dataset.error;},20000);
import('./reader.js').catch(()=>{status.textContent=status.dataset.error;}).finally(()=>clearTimeout(timeout));

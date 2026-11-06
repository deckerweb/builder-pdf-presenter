/* One reader at a time. No document is fetched until a reading link is activated. */
(() => {
  'use strict';
  document.addEventListener('click',event=>{if(event.target.closest('.bpp-builder-preview a')){event.preventDefault();event.stopPropagation();}},true);
  let dialog, iframe, opener, collection = [], current = 0;
  const loadCard = card => { const link=card.querySelector('a[data-bpp-open]'); if(!link)return; dialog.querySelector('#bpp-modal-title').textContent=card.dataset.bppTitle;iframe.title=card.dataset.bppTitle;iframe.src=link.href;dialog.querySelector('.bpp-prev-doc').disabled=current===0;dialog.querySelector('.bpp-next-doc').disabled=current===collection.length-1; };
  document.addEventListener('click', event => {
    const link = event.target.closest('a[data-bpp-open]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (typeof HTMLDialogElement === 'undefined') return;
    if (!dialog) {
      dialog = document.createElement('dialog');
      dialog.className = 'bpp-modal'; dialog.hidden = true;
      const bar = document.createElement('div'); bar.className = 'bpp-modal-bar';
      const title = document.createElement('span'); title.id = 'bpp-modal-title';
      dialog.setAttribute('aria-labelledby', title.id);
      const form = document.createElement('form'); form.method = 'dialog';
      const close = document.createElement('button'); close.type = 'submit';
      form.append(close); bar.append(title, form);
      iframe = document.createElement('iframe');
      iframe.setAttribute('allow', 'fullscreen');
      const nav=document.createElement('div');nav.className='bpp-document-nav';const prev=document.createElement('button'),next=document.createElement('button');prev.type=next.type='button';prev.className='bpp-prev-doc';next.className='bpp-next-doc';prev.textContent='←';next.textContent='→';prev.addEventListener('click',()=>{if(current>0){current--;loadCard(collection[current]);}});next.addEventListener('click',()=>{if(current<collection.length-1){current++;loadCard(collection[current]);}});nav.append(prev,next);bar.insertBefore(nav,form);dialog.append(bar, iframe); document.body.append(dialog);
      dialog.addEventListener('click', e => { if (e.target === dialog) HTMLDialogElement.prototype.close.call(dialog); });
      dialog.addEventListener('close', () => {
        iframe.src = 'about:blank'; dialog.hidden = true;
        document.documentElement.classList.remove('bpp-reader-open');
        opener?.focus();
      });
      // Escape in an iframe does not reach the outer dialog.
      window.addEventListener('message', e => {
        if (e.origin === location.origin && e.source === iframe.contentWindow && e.data === 'bpp-close-reader' && dialog.open) HTMLDialogElement.prototype.close.call(dialog);
      });
    }
    const card = link.closest('.bpp-card');
    const group=card.closest('.bpp-collection') || card.closest('[data-bpp-query]') || card.closest('ul') || card.parentElement;
    const cards=group.dataset.bppQuery?[...document.querySelectorAll('[data-bpp-query]')].filter(n=>n.dataset.bppQuery===group.dataset.bppQuery).flatMap(n=>[...n.querySelectorAll('.bpp-card')]):[...group.querySelectorAll('.bpp-card')];
    collection=cards.filter(c=>c.querySelector('a[data-bpp-open]'));if(!collection.includes(card))collection=[card];current=collection.indexOf(card);
    dialog.querySelector('.bpp-prev-doc').setAttribute('aria-label',group.dataset.bppPrevious||'Previous document');dialog.querySelector('.bpp-next-doc').setAttribute('aria-label',group.dataset.bppNext||'Next document');
    dialog.querySelector('#bpp-modal-title').textContent = card.dataset.bppTitle;
    dialog.querySelector('form button').textContent = card.dataset.bppClose;
    iframe.title = card.dataset.bppTitle;
    opener = link; dialog.hidden = false;
    try {
      HTMLDialogElement.prototype.showModal.call(dialog);
      event.preventDefault();
      document.documentElement.classList.add('bpp-reader-open');
      loadCard(card);
      dialog.querySelector('form button').focus();
    } catch {
      dialog.hidden = true; // The ordinary reader link remains the navigation fallback.
    }
  });
})();

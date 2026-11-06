/* A single-page, local PDF.js reader. A destroyed iframe releases its document and worker. */
import { getDocument, GlobalWorkerOptions, TextLayer } from '../vendor/pdfjs/build/pdf.mjs';
GlobalWorkerOptions.workerSrc = new URL('../vendor/pdfjs/build/pdf.worker.mjs', import.meta.url).href;
document.querySelectorAll('.bpp-watermark').forEach(node=>{node.style.opacity=String(Number(node.dataset.opacity)/100);node.style.fontSize=node.dataset.size+'px';});
const byId = id => document.getElementById(id);
const status = byId('reader-status');
const stage = byId('reader-stage');
const pageBox = byId('reader-page');
const textBox = byId('pdf-text');
const canvas = byId('pdf-canvas');
const root = new URL('../vendor/pdfjs/', import.meta.url).href;
const memoryKey='bpp-page:'+document.body.dataset.bppUrl;
const remember=document.body.dataset.bppRemember==='1';
const memory=window.parent!==window?window.parent:window;
try{memory.BPPReadingPositions=memory.BPPReadingPositions||new Map();}catch{}
let pdf, number = 1, scale = document.body.dataset.bppZoom === 'fit' ? null : Number(document.body.dataset.bppZoom);
let rendering, textLayer, sequence = 0, resizeTimer;
const task = getDocument({ url: document.body.dataset.bppUrl, cMapUrl: root + 'cmaps/', cMapPacked: true, standardFontDataUrl: root + 'standard_fonts/', wasmUrl: root + 'wasm/', isEvalSupported: false });

/** Cancel stale work before drawing another page or zoom level. */
async function draw(resetScroll = false) {
  const generation = ++sequence;
  if (rendering) { rendering.cancel(); try { await rendering.promise; } catch {} }
  textLayer?.cancel();
  if (generation !== sequence || !pdf) return;
  status.textContent = status.dataset.loading;
  try {
    const page = await pdf.getPage(number);
    if (generation !== sequence) return;
    const original = page.getViewport({ scale: 1 });
    const padding = parseFloat(getComputedStyle(stage).paddingLeft) + parseFloat(getComputedStyle(stage).paddingRight);
    const effectiveScale = scale || Math.min(3, Math.max(.2, (stage.clientWidth - padding) / original.width));
    const viewport = page.getViewport({ scale: effectiveScale });
    const density = Math.min(devicePixelRatio || 1, 2);
    canvas.width = Math.floor(viewport.width * density); canvas.height = Math.floor(viewport.height * density);
    canvas.style.width = `${viewport.width}px`; canvas.style.height = `${viewport.height}px`;
    pageBox.style.width = `${viewport.width}px`; pageBox.style.height = `${viewport.height}px`;
    pageBox.style.setProperty('--scale-factor', effectiveScale);
    pageBox.style.setProperty('--total-scale-factor', effectiveScale);
    textBox.replaceChildren();
    rendering = page.render({ canvasContext: canvas.getContext('2d'), viewport, transform: density === 1 ? null : [density, 0, 0, density, 0, 0] });
    await rendering.promise;
    if (generation !== sequence) return;
    textLayer = new TextLayer({ textContentSource: page.streamTextContent(), container: textBox, viewport });
    await textLayer.render();
    if (generation !== sequence) return;
    if (byId('page')) byId('page').value = number;
    if (byId('previous')) byId('previous').disabled = number === 1;
    if (byId('next')) byId('next').disabled = number === pdf.numPages;
    status.textContent = `${status.dataset.loaded} · ${number} / ${pdf.numPages}`;
    if(remember)try{memory.BPPReadingPositions.set(memoryKey,number);}catch{}
    if (resetScroll) { stage.scrollTop = 0; stage.scrollLeft = 0; }
  } catch (error) {
    if (generation === sequence && error.name !== 'RenderingCancelledException' && error.name !== 'AbortException') status.textContent = status.dataset.error;
  }
}
function move(target) {
  if (!pdf || !Number.isFinite(target)) return;
  number = Math.min(pdf.numPages, Math.max(1, Math.floor(target))); draw(true);
}
byId('previous')?.addEventListener('click', () => move(number - 1));
byId('next')?.addEventListener('click', () => move(number + 1));
byId('page')?.addEventListener('change', e => move(Number(e.target.value)));
byId('fit')?.addEventListener('click', () => { scale = null; draw(); });
byId('zoom-in')?.addEventListener('click', () => { scale = Math.min(3, (scale || parseFloat(pageBox.style.getPropertyValue('--scale-factor')) || 1) + .2); draw(); });
byId('zoom-out')?.addEventListener('click', () => { scale = Math.max(.3, (scale || parseFloat(pageBox.style.getPropertyValue('--scale-factor')) || 1) - .2); draw(); });
byId('fullscreen')?.addEventListener('click', async () => {
  try { if (document.fullscreenElement) await document.exitFullscreen(); else await document.documentElement.requestFullscreen(); }
  catch { status.textContent = status.dataset.fullscreenError; }
});
byId('search-form')?.addEventListener('submit', e => {
  e.preventDefault();
  const query = byId('search').value.trim().toLocaleLowerCase(); let count = 0;
  textBox.querySelectorAll('span').forEach(span => {
    const found = Boolean(query) && span.textContent.toLocaleLowerCase().includes(query);
    span.classList.toggle('bpp-match', found); if (found) count++;
  });
  status.textContent = `${status.dataset.matches} ${count}`;
  textBox.querySelector('.bpp-match')?.scrollIntoView({ block: 'center', inline: 'nearest' });
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && window.parent !== window) window.parent.postMessage('bpp-close-reader', location.origin);
  if (['INPUT', 'TEXTAREA', 'SELECT', 'BUTTON', 'A'].includes(e.target.tagName)) return;
  if (byId('page') && e.key === 'ArrowRight') { e.preventDefault(); move(number + 1); }
  if (byId('page') && e.key === 'ArrowLeft') { e.preventDefault(); move(number - 1); }
});
window.addEventListener('resize', () => { clearTimeout(resizeTimer); resizeTimer = setTimeout(() => { if (!scale && pdf) draw(); }, 150); });
window.addEventListener('pagehide', () => { sequence++; textLayer?.cancel(); rendering?.cancel(); task.destroy(); });
try {
  pdf = await task.promise;
  if(remember)try{number=Math.max(1,Math.min(pdf.numPages,memory.BPPReadingPositions.get(memoryKey)||1));}catch{}
  if (byId('page')) byId('page').max = pdf.numPages;
  if (byId('page-total')) byId('page-total').textContent = `/ ${pdf.numPages}`;
  await draw();
} catch { status.textContent = status.dataset.error; }

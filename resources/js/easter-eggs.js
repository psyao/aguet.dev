/* aguet.dev — vim easter eggs (progressive enhancement, keyboard-only).
   Registered from app.js. Stores are resolved lazily inside handlers because
   they're created on `alpine:init`; reading them at import time would race. */

const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

// Shared NORMAL-mode guard: no eggs while typing or while a layer is open.
function blocked(e) {
  const tgt = e.target;
  if (tgt && tgt.closest && (tgt.matches('input,textarea,select') || tgt.closest('[contenteditable]'))) return true;
  const A = window.Alpine;
  if (!A) return false;
  const cmdk = A.store('cmdk');
  const contact = A.store('contact');
  return !!((cmdk && cmdk.isOpen) || (contact && contact.isOpen));
}

// Motion targets: the hero (page top) followed by the tab-linked sections.
// The hero (`<section class="hero section">`, sections/hero.blade.php) has no
// tab link, so without prepending it `gg`/`k` could never reach the real top —
// they'd stop at `#about` and `gg` would land on about instead of the top.
function sections() {
  const tabbed = $$('.tabs a')
    .map((a) => (a.getAttribute('href') || '').split('#')[1])
    .filter(Boolean)
    .map((id) => document.getElementById(id))
    .filter(Boolean);
  const hero = document.querySelector('.hero');
  return hero ? [hero, ...tabbed] : tabbed;
}

// Index of the target whose top is nearest the viewport top, computed live
// (the scrollspy `.active` class lags during smooth-scroll and at page edges).
function currentIndex(secs) {
  let idx = 0;
  let best = Infinity;
  secs.forEach((s, i) => {
    const d = Math.abs(s.getBoundingClientRect().top);
    if (d < best) { best = d; idx = i; }
  });
  return idx;
}

function go(secs, i) {
  const n = secs.length;
  if (!n) return;
  const clamped = Math.max(0, Math.min(n - 1, i));
  secs[clamped].scrollIntoView({ behavior: 'smooth' });
}

function initMotions() {
  let ggPending = false;
  let ggTimer = null;

  document.addEventListener('keydown', (e) => {
    if (e.metaKey || e.ctrlKey || e.altKey) return;
    if (blocked(e)) { ggPending = false; return; }
    if (e.repeat) return; // ignore autorepeat for the gg sequence

    const secs = sections();
    if (!secs.length) return;
    const cur = currentIndex(secs);

    if (e.key === 'j') { e.preventDefault(); go(secs, cur + 1); ggPending = false; return; }
    if (e.key === 'k') { e.preventDefault(); go(secs, cur - 1); ggPending = false; return; }
    if (e.key === 'G') { e.preventDefault(); go(secs, secs.length - 1); ggPending = false; return; }

    if (e.key === 'g' && !e.shiftKey) {
      if (ggPending) {
        e.preventDefault();
        ggPending = false;
        clearTimeout(ggTimer);
        go(secs, 0);
      } else {
        ggPending = true;
        clearTimeout(ggTimer);
        ggTimer = setTimeout(() => { ggPending = false; }, 500);
      }
      return;
    }

    ggPending = false; // any other key cancels a pending gg
  });
}

export function initEasterEggs() {
  initMotions();
}

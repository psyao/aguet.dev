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
  secs[clamped].scrollIntoView({ behavior: reduce ? 'auto' : 'smooth' });
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

const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const KONAMI = ['arrowup', 'arrowup', 'arrowdown', 'arrowdown',
  'arrowleft', 'arrowright', 'arrowleft', 'arrowright', 'b', 'a'];

let rainStop = null; // non-null while running (also the double-trigger guard)

function accent() {
  const v = getComputedStyle(document.documentElement).getPropertyValue('--color-accent').trim();
  return v || '#7aa46b';
}

function startRain() {
  if (rainStop) return; // already running

  const canvas = document.createElement('canvas');
  canvas.style.cssText = 'position:fixed;inset:0;z-index:150;pointer-events:none';
  document.body.appendChild(canvas);
  const ctx = canvas.getContext('2d');

  const FONT = 14;
  let drops = []; // row position per column; each falls and resets independently
  function size() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    const n = Math.ceil(canvas.width / FONT);
    // Stagger starts above the top so columns aren't all in lockstep.
    drops = new Array(n).fill(0).map(() => Math.floor(Math.random() * -40));
  }
  size();
  window.addEventListener('resize', size);

  const glyphs = 'アイウエオカキクabcdef01{}[]<>/;:=+-$#'.split('');
  const color = accent();
  let raf = 0;
  function draw() {
    ctx.fillStyle = 'rgba(3,7,5,0.08)'; // fade trail
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = color;
    ctx.font = FONT + 'px monospace';
    for (let i = 0; i < drops.length; i++) {
      const ch = glyphs[Math.floor(Math.random() * glyphs.length)];
      ctx.fillText(ch, i * FONT, drops[i] * FONT);
      // Past the bottom: reset to top at random so columns desync (no gaps).
      if (drops[i] * FONT > canvas.height && Math.random() > 0.975) drops[i] = 0;
      else drops[i]++;
    }
    raf = requestAnimationFrame(draw);
  }
  raf = requestAnimationFrame(draw);

  let autoTimer = null;
  function stop() {
    if (!rainStop) return;
    rainStop = null;
    cancelAnimationFrame(raf);
    window.removeEventListener('resize', size);
    window.removeEventListener('keydown', onKey);
    clearTimeout(autoTimer);
    canvas.remove();
  }
  function onKey() { stop(); }

  rainStop = stop;
  // Attach the dismiss listener on the next tick so the keydown that COMPLETED
  // the konami sequence doesn't immediately tear the canvas back down.
  setTimeout(() => { window.addEventListener('keydown', onKey); }, 0);
  autoTimer = setTimeout(stop, 8000); // safety dismiss for pointer-only users
}

function initKonami() {
  let buf = [];
  document.addEventListener('keydown', (e) => {
    if (e.metaKey || e.ctrlKey || e.altKey) { buf = []; return; }
    if (blocked(e)) { buf = []; return; }
    buf.push(e.key.toLowerCase());
    if (buf.length > KONAMI.length) buf.shift();
    if (buf.length === KONAMI.length && KONAMI.every((k, i) => buf[i] === k)) {
      buf = [];
      if (reduce) {
        const vim = window.Alpine && window.Alpine.store('vim');
        if (vim) vim.flash('↑↑↓↓←→←→BA 🎉');
        return;
      }
      startRain();
    }
  });
}

export function initEasterEggs() {
  initMotions();
  initKonami();
}

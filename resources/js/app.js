/* aguet.dev — Terminal v2 interactions, plain modules with no framework.
   Adapted from the Claude Design prototype (shared/terminal-v2.js).

   Kami's front Content Security Policy runs no inline handler and no eval, so
   every behaviour is wired here, on data-* hooks in the Blade partials.

   - Language is server-side (URL /  vs /en); the command palette's
     "switch language" navigates to the alternate URL.
   - Per-locale UI strings + palette data come from the #aguet-config JSON
     block, rendered by the Blade layout for the current locale only.
   The page is fully functional without this script (progressive enhancement). */

import { initEasterEggs } from './easter-eggs';

const CFG = (() => {
  const fallback = { locale: 'fr', altUrl: '/en', i18n: {}, projects: [], contact: {} };
  try { return JSON.parse(document.getElementById('aguet-config').textContent) || fallback; } catch (_) { return fallback; }
})();
const t = (k, fallback) => (CFG.i18n && CFG.i18n[k] != null ? CFG.i18n[k] : (fallback || ''));
const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
// Decode a base64 string (used to keep the email out of the HTML source).
const deob = (b64) => { try { return atob(b64); } catch (_) { return ''; } };
// Copy text to the clipboard, with a hidden-textarea fallback for old browsers.
async function writeClipboard(text) {
  try { await navigator.clipboard.writeText(text); return; }
  catch (_) { /* fall through */ }
  const ta = document.createElement('textarea');
  ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
  document.body.appendChild(ta); ta.select();
  try { document.execCommand('copy'); } catch (__) {}
  ta.remove();
}

// Visible, tabbable elements inside a dialog root (excludes tabindex=-1 and
// off-screen nodes). Shared by the contact modal + command palette focus traps.
function tabbable(root) {
  const sel = 'a[href],button:not([disabled]),input:not([disabled]),textarea:not([disabled]),select:not([disabled]),[tabindex]';
  return Array.from(root.querySelectorAll(sel)).filter((el) => el.tabIndex >= 0 && el.offsetParent !== null);
}

// Mark the page shell behind an open dialog inert (unfocusable + hidden from
// AT), so screen-reader/keyboard users can't wander behind it. `alsoInert` is
// the sibling dialog: it is always closed while this one is open, so inerting it
// too is harmless. The open dialog's own root is never in this list, so it stays
// live (that is how "inert everything but the open dialog" is achieved).
const DIALOG_SHELL = ['.chrome', '.tabs', '#content', '.statusbar'];
function setBackgroundInert(on, alsoInert) {
  [...DIALOG_SHELL, alsoInert].forEach((s) => {
    const el = document.querySelector(s);
    if (!el) return;
    on ? el.setAttribute('inert', '') : el.removeAttribute('inert');
  });
}

// Wrap Tab focus inside `root` (a dialog panel). Call from @keydown.tab.
function trapTab(e, root) {
  if (!root) return;
  const f = tabbable(root);
  if (!f.length) { e.preventDefault(); return; }
  const first = f[0];
  const last = f[f.length - 1];
  if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
  else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
}

/* ───────────────── BOOT / TYPING INTRO (imperative, one-shot) ───────────────── */
async function typeInto(el, text, cps) {
  el.classList.add('typing');
  for (let i = 0; i <= text.length; i++) {
    el.textContent = text.slice(0, i);
    if (i < text.length) await sleep(1000 / cps + (Math.random() * 26 - 13));
  }
  el.classList.remove('typing');
}
function revealNow() {
  $$('.boot-hide').forEach((el) => el.classList.add('in'));
  $$('.type').forEach((el) => { if (el.dataset.type) el.textContent = el.dataset.type; });
  const c = $('.hero .cline'); if (c) c.classList.add('in');
  document.documentElement.classList.remove('boot');
}
async function boot() {
  const hero = $('.hero');
  if (!hero) return;
  if (reduce || !document.documentElement.classList.contains('boot')) { revealNow(); return; }
  const t0 = $('.type[data-type="whoami"]');
  const t1 = $('.type[data-type="cat headline.txt"]');
  const out0 = $('.hero .who-out');
  const h1 = $('.hero h1');
  const stagger = ['.hero .sub', '.hero .kv', '.hero .tui-row'];
  $$('.type').forEach((el) => (el.textContent = ''));
  await sleep(280);
  if (t0) await typeInto(t0, 'whoami', 30);
  await sleep(170);
  if (out0) out0.classList.add('in');
  await sleep(300);
  if (t1) await typeInto(t1, 'cat headline.txt', 32);
  await sleep(160);
  if (h1) h1.classList.add('in');
  for (const sel of stagger) { await sleep(110); const el = $(sel); if (el) el.classList.add('in'); }
  await sleep(120);
  const c = $('.hero .cline'); if (c) c.classList.add('in');
  await sleep(520);
  document.documentElement.classList.remove('boot');
}
function scrollspy() {
  const tabs = $$('.tabs a');
  const map = {};
  tabs.forEach((a) => { const id = (a.getAttribute('href') || '').split('#')[1]; if (id) map[id] = a; });
  const ids = Object.keys(map);
  if (!ids.length) return;
  const io = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (e.isIntersecting) {
        tabs.forEach((tb) => tb.classList.remove('active'));
        map[e.target.id] && map[e.target.id].classList.add('active');
      }
    });
  }, { rootMargin: '-45% 0px -50% 0px', threshold: 0 });
  ids.forEach((id) => { const s = document.getElementById(id); if (s) io.observe(s); });
}

/* ───────────────── COMPONENTS ───────────────── */

// Vim command-line echo: ex-commands flash a transient message here, shown
// in the statusbar (the bottom line, like vim's command-line). Auto-clears.
const vim = {
  _t: null,
  flash(m) {
    const el = $('[data-vim-echo]');
    if (!el) return;
    el.textContent = m;
    el.hidden = false;
    clearTimeout(this._t);
    this._t = setTimeout(() => { el.textContent = ''; el.hidden = true; }, 2500);
  },
};

// ── Colorscheme ──────────────────────────────────────────────────────
// :colorscheme <name> swaps the --color-* block via a body[data-theme]
// attribute (same mechanism as data-fx / data-density), persisted in
// localStorage and re-applied pre-paint by the inline script in the layout.
const THEMES = ['default', 'gruvbox', 'nord', 'crt', 'light'];

function applyTheme(name) {
  if (!THEMES.includes(name)) return false;
  if (name === 'default') {
    delete document.body.dataset.theme;
    try { localStorage.removeItem('theme'); } catch (e) { /* private mode */ }
  } else {
    document.body.dataset.theme = name;
    try { localStorage.setItem('theme', name); } catch (e) { /* private mode */ }
  }
  return true;
}

// Shows or hides a dialog: `hidden` for layout, `show` for its transition, the
// rest of the page inert and unscrollable behind it, focus handed to `focusTo`.
function showDialog(root, on, otherDialog, focusTo) {
  if (root) { root.hidden = !on; root.classList.toggle('show', on); }
  setBackgroundInert(on, otherDialog);
  document.body.style.overflow = on ? 'hidden' : '';
  requestAnimationFrame(() => { const el = focusTo(); if (el && typeof el.focus === 'function') el.focus(); });
}

// Command palette (⌘K): fuzzy filter, keyboard nav, grouped results.
const cmdk = {
  isOpen: false,
  active: 0,
  items: [],
  trigger: null,

  get root() { return document.getElementById('cmdk'); },
  get input() { return document.getElementById('cmdk-input'); },
  get query() { return this.input ? this.input.value : ''; },

  build() {
    const go = (id) => () => { this.close(); const s = document.getElementById(id); if (s) s.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth' }); };
    const open = (u) => () => window.open(u, '_blank', 'noopener');
    const nav = t('cmd.nav', 'Navigation');
    const act = t('cmd.actions', 'Actions');
    const out = [
      { g: nav, label: t('nav.about', 'about'), hint: '~/about', run: go('about') },
      { g: nav, label: t('nav.skills', 'skills'), hint: '~/skills', run: go('skills') },
      { g: nav, label: t('nav.projects', 'projects'), hint: '~/projects', run: go('projects') },
      { g: nav, label: t('nav.contact', 'contact'), hint: '~/contact', run: go('contact') },
    ];
    (CFG.projects || []).forEach((p) => { if (p.url) out.push({ g: 'projects', label: p.label, hint: p.host, run: open(p.url) }); });
    out.push({ g: act, label: t('cmd.lang', 'Switch language'), hint: 'fr ↔ en', run: () => { window.location.href = CFG.altUrl; } });
    const c = CFG.contact || {};
    const email = deob(c.emailEnc || '');
    if (email) out.push({ g: act, label: t('cmd.email', 'Email'), hint: email, run: () => { window.location.href = 'mailto:' + email; } });
    if (c.linkedin) out.push({ g: act, label: t('cmd.linkedin', 'LinkedIn'), hint: c.linkedinLabel || '', run: open(c.linkedin) });
    if (c.github) out.push({ g: act, label: t('cmd.github', 'GitHub'), hint: c.githubLabel || '', run: open(c.github) });
    this.items = out;
  },

  // A query starting with ':' is vim command-line mode, not fuzzy search.
  get commandMode() { return this.query.trim().toLowerCase().startsWith(':'); },

  // `:help` / `:h` lists the eggs as ordinary palette rows (reuses list UI).
  get helpRows() {
    const g = t('cmd.actions', 'Actions');
    return ['help.motions', 'help.jumps', 'help.excmd', 'help.colorscheme', 'help.konami'].map((k, i) => ({
      g, label: t(k, k), hint: '', run: () => this.close(), _i: i,
    }));
  },

  get filtered() {
    const raw = this.query.trim().toLowerCase();
    if (raw.startsWith(':')) {
      return (raw === ':help' || raw === ':h') ? this.helpRows : [];
    }
    const list = raw ? this.items.filter((c) => (c.label + ' ' + c.hint + ' ' + c.g).toLowerCase().includes(raw)) : this.items;
    return list.map((c, i) => ({ ...c, _i: i }));
  },

  // Renders the grouped list (group header + its items) from the flat filtered list.
  render() {
    const box = document.getElementById('cmdk-groups');
    if (!box) return;
    const f = this.filtered;
    const groups = [];
    let cur = null;
    f.forEach((it) => {
      if (!cur || cur.g !== it.g) { cur = { g: it.g, items: [] }; groups.push(cur); }
      cur.items.push(it);
    });
    box.replaceChildren(...groups.map((grp) => {
      const wrap = document.createElement('div');
      const head = document.createElement('div');
      head.className = 'cmdk-grp';
      head.textContent = grp.g;
      wrap.append(head);
      grp.items.forEach((item) => {
        const b = document.createElement('button');
        b.className = 'cmdk-item';
        b.type = 'button';
        b.dataset.i = String(item._i);
        b.classList.toggle('on', item._i === this.active);
        const span = (cls, text, hidden) => { const s = document.createElement('span'); s.className = cls; s.textContent = text; if (hidden) s.setAttribute('aria-hidden', 'true'); return s; };
        b.append(span('ci-prompt', '›', true), span('ci-label', item.label), span('ci-hint', item.hint));
        b.addEventListener('click', () => this.run(item));
        b.addEventListener('mousemove', () => this.setActive(item._i));
        wrap.append(b);
      });
      return wrap;
    }));
    const empty = $('#cmdk .cmdk-empty');
    if (empty) empty.hidden = !(f.length === 0 && !this.commandMode);
  },

  setActive(i) {
    this.active = i;
    $$('#cmdk-list .cmdk-item').forEach((el) => el.classList.toggle('on', el.dataset.i === String(i)));
  },

  open(seed = '') {
    if (!this.items.length) this.build();
    if (this.input) this.input.value = seed;
    this.active = 0;
    this.trigger = document.activeElement;
    this.isOpen = true;
    this.render();
    showDialog(this.root, true, '#contact-modal', () => this.input);
    updateMode();
  },
  close() {
    if (!this.isOpen) return;
    this.isOpen = false;
    const tr = this.trigger;
    this.trigger = null;
    showDialog(this.root, false, '#contact-modal', () => tr);
    updateMode();
  },
  toggle() { this.isOpen ? this.close() : this.open(); },
  run(item) { this.close(); item.run(); },
  move(d) {
    const n = this.filtered.length; if (!n) return;
    this.setActive((this.active + d + n) % n);
    // Keep the highlighted row visible when nav runs past the viewport.
    const el = $('#cmdk-list .cmdk-item.on');
    if (el) el.scrollIntoView({ block: 'nearest' });
  },
  enter() {
    if (this.commandMode) {
      // `:help`/`:h` populate `filtered` with selectable help rows — Enter runs
      // the active one (its run = close), so arrow-select + Enter works. Every
      // other ':' command has empty `filtered`, so fall through to execEx.
      const f = this.filtered;
      if (f.length) { this.run(f[this.active]); return; }
      this.execEx(this.query.trim().toLowerCase());
      return;
    }
    const f = this.filtered; if (f[this.active]) this.run(f[this.active]);
  },

  // Vim ex-commands. Always close the palette first so the statusbar echo
  // (z-index 30) isn't hidden behind the palette/backdrop (z-index 100).
  // Note: `:help`/`:h` never reach here — their non-empty `filtered` is handled
  // by enter() above.
  execEx(cmd) {
    const say = (m) => { this.close(); vim.flash(m); };

    // :colorscheme <name> (vim abbrev :colo). Bare form lists the options.
    const cs = cmd.match(/^:colo(?:rscheme)?\b\s*(\S*)$/);
    if (cs) {
      const name = cs[1];
      if (!name) { say('colorscheme: ' + THEMES.join(' ')); return; }
      if (applyTheme(name)) say(':colorscheme ' + name);
      else say(t('cmd.e185', 'E185: Cannot find color scheme ') + name);
      return;
    }

    switch (cmd) {
      case ':q': case ':quit':
        say(t('cmd.q', 'E37: No write since last change (add ! to override)')); break;
      case ':q!':
        this.close(); break;
      case ':wq': case ':x': case ':w':
        say(t('cmd.wq', '"aguet.dev" written — nothing to save')); break;
      default:
        say(t('cmd.e492', 'E492: Not an editor command: ') + cmd.slice(1)); break;
    }
  },
};

// Contact modal: accessible dialog shell. Like cmdk it hand-rolls a focus
// trap, but also returns focus to the trigger and marks the background inert.
const contact = {
  isOpen: false,
  trigger: null,

  // Focus goes to the confirmation or the errors summary when the page holds
  // one, else to the first control. Opened on load, focus returns to the CTA.
  open() {
    if (this.isOpen) return;
    const active = document.activeElement;
    this.trigger = active && active !== document.body ? active : $('[data-contact-open]');
    this.isOpen = true;
    showDialog(document.getElementById('contact-modal'), true, '#cmdk', () => {
      const panel = document.getElementById('contact-modal-panel');
      return panel && ($('[data-contact-focus]', panel) || tabbable(panel)[0] || panel);
    });
  },

  // Hide only: a draft in the form is kept (ESC, backdrop, ✕).
  close() {
    if (!this.isOpen) return;
    this.isOpen = false;
    const tr = this.trigger;
    this.trigger = null;
    showDialog(document.getElementById('contact-modal'), false, '#cmdk', () => tr);
  },
};

// Statusbar: live vim-mode pill. COMMAND while the palette is open, INSERT in a
// field, VISUAL with a page selection, NORMAL otherwise.
let insertMode = false;
let visualMode = false;
function updateMode() {
  const el = $('[data-mode]');
  if (!el) return;
  el.textContent = cmdk.isOpen ? 'COMMAND' : insertMode ? 'INSERT' : visualMode ? 'VISUAL' : 'NORMAL';
}

// "3 days ago" / "il y a 3 jours", from a signed number of seconds.
function relativeTime(seconds) {
  const units = [['year', 31536000], ['month', 2592000], ['week', 604800], ['day', 86400], ['hour', 3600], ['minute', 60]];
  const [unit, size] = units.find(([, s]) => Math.abs(seconds) >= s) || ['second', 1];
  return new Intl.RelativeTimeFormat(CFG.locale, { numeric: 'auto' }).format(Math.round(seconds / size), unit);
}

function initStatusbar() {
  document.addEventListener('selectionchange', () => {
    const s = window.getSelection();
    const txt = s ? s.toString().trim() : '';
    let inUI = false;
    if (txt && s && s.anchorNode) {
      const el = s.anchorNode.nodeType === 1 ? s.anchorNode : s.anchorNode.parentElement;
      if (el && el.closest('#cmdk, #contact-modal-panel')) inUI = true;
    }
    visualMode = !!txt && !inUI;
    updateMode();
  });
  document.addEventListener('focusin', (e) => {
    insertMode = !!(e.target && e.target.closest && e.target.closest('input,textarea,select,[contenteditable]'));
    updateMode();
  });
  document.addEventListener('focusout', () => { insertMode = false; updateMode(); });

  // Commit popover: server-rendered content, toggled here; closes on Escape or a click outside.
  const branch = $('[data-branch]');
  if (branch) {
    const trigger = $('.branch-trigger', branch);
    const pop = $('.sb-pop', branch);
    const show = (on) => { pop.hidden = !on; trigger.setAttribute('aria-expanded', String(on)); };
    trigger.addEventListener('click', () => show(pop.hidden));
    branch.addEventListener('keydown', (e) => { if (e.key === 'Escape') show(false); });
    document.addEventListener('click', (e) => { if (!branch.contains(e.target)) show(false); });
  }

  // The deploy's relative date and the year, computed here: the page is served
  // from Kami's page cache, where the server's values would freeze.
  const relDate = $('[data-reldate]');
  if (relDate) {
    const seconds = (Date.parse(relDate.getAttribute('datetime')) - Date.now()) / 1000;
    if (!Number.isNaN(seconds)) relDate.textContent = relativeTime(seconds);
  }
  const year = $('[data-year]');
  if (year) year.textContent = '© ' + new Date().getFullYear();

  // Live clock (Europe/Zurich), tmux-style status bar segment.
  const clock = $('[data-clock]');
  if (clock) {
    const tick = () => {
      clock.textContent = new Date().toLocaleTimeString(CFG.locale === 'fr' ? 'fr-CH' : 'en-GB', {
        hour: '2-digit', minute: '2-digit', hourCycle: 'h23', timeZone: 'Europe/Zurich',
      });
    };
    tick();
    setInterval(tick, 15000);
  }
}

// Click-to-copy contact rows with transient "copied" feedback. The email is
// base64-encoded in the markup and rebuilt here, so no plaintext "user@domain"
// sits in the HTML source for naive scrapers; without JS its row stays hidden.
function initContactRows() {
  $$('[data-email], [data-copy]').forEach((row) => {
    let value = row.dataset.copy || '';
    if (row.dataset.email !== undefined) {
      value = deob(row.dataset.email);
      const a = $('a', row);
      a.href = 'mailto:' + value;
      a.textContent = '"' + value + '"';
      row.hidden = false;
    }
    const btn = $('.cp', row);
    let timer = null;
    btn.addEventListener('click', async () => {
      await writeClipboard(value);
      btn.classList.add('ok');
      btn.textContent = t('contact.copied', 'copied');
      clearTimeout(timer);
      timer = setTimeout(() => { btn.classList.remove('ok'); btn.textContent = t('contact.copy', 'copy'); }, 1500);
    });
  });
}

function initDialogs() {
  $$('[data-cmdk-open]').forEach((el) => el.addEventListener('click', () => cmdk.open()));
  $$('[data-cmdk-toggle]').forEach((el) => el.addEventListener('click', () => cmdk.toggle()));
  $$('[data-cmdk-close]').forEach((el) => el.addEventListener('click', () => cmdk.close()));
  $$('[data-contact-close]').forEach((el) => el.addEventListener('click', () => contact.close()));
  $$('[data-contact-open]').forEach((el) => {
    el.setAttribute('aria-haspopup', 'dialog');
    el.addEventListener('click', (e) => { e.preventDefault(); contact.open(); });
  });

  const input = document.getElementById('cmdk-input');
  if (input) {
    input.addEventListener('input', () => { cmdk.active = 0; cmdk.render(); });
    input.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowDown') { e.preventDefault(); cmdk.move(1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); cmdk.move(-1); }
      else if (e.key === 'Enter') { e.preventDefault(); cmdk.enter(); }
    });
  }
  const cmdkPanel = document.getElementById('cmdk-panel');
  if (cmdkPanel) cmdkPanel.addEventListener('keydown', (e) => { if (e.key === 'Tab') trapTab(e, cmdkPanel); });
  const contactPanel = document.getElementById('contact-modal-panel');
  if (contactPanel) contactPanel.addEventListener('keydown', (e) => { if (e.key === 'Tab') trapTab(e, contactPanel); });

  // The contact Form posts a full page: its confirmation or its errors come
  // back on a fresh load, inside the modal, which opens itself over them.
  const modal = document.getElementById('contact-modal');
  if (modal && modal.hasAttribute('data-contact-autoopen')) contact.open();
}

// The message's character counter, kept in step on typing and on reset.
function initContactCounter() {
  const count = $('[data-cf-count]');
  const message = document.getElementById('message');
  if (!count || !message) return;
  const update = () => { count.textContent = String(message.value.length); };
  message.addEventListener('input', update);
  // `reset` fires before the fields are cleared.
  if (message.form) message.form.addEventListener('reset', () => setTimeout(update));
}

boot();
scrollspy();
initStatusbar();
initContactRows();
initDialogs();
initContactCounter();

// ⌘K is Mac-only; the binding accepts Ctrl too, so show "Ctrl K" off Mac.
{
  const plat = (navigator.userAgentData && navigator.userAgentData.platform) || navigator.platform || '';
  if (!/mac/i.test(plat)) {
    document.querySelectorAll('kbd.kmod').forEach((k) => { k.textContent = 'Ctrl K'; });
  }
}

// Global shortcuts: ⌘K / Ctrl-K toggles the palette; ':' opens it (vim reflex).
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') { cmdk.close(); contact.close(); return; }

  if ((e.key === 'k' || e.key === 'K') && (e.metaKey || e.ctrlKey)) {
    e.preventDefault();
    // Don't stack the palette over an open contact modal — closing it would
    // unlock scroll + un-inert the background the modal still needs held.
    if (contact.isOpen) return;
    cmdk.toggle();
    return;
  }

  // ':' is a bare keystroke — ignore while typing or when a layer is already open.
  // shiftKey is allowed (':' needs Shift on many layouts); meta/ctrl/alt are not.
  if (e.key === ':' && !e.metaKey && !e.ctrlKey && !e.altKey) {
    const tgt = e.target;
    if (tgt && tgt.closest && (tgt.matches('input,textarea,select') || tgt.closest('[contenteditable]'))) return;
    if (cmdk.isOpen || contact.isOpen) return;
    e.preventDefault();
    cmdk.open(':');
  }
});

// Vim easter eggs: j/k section motions + konami matrix rain.
initEasterEggs({ cmdk, contact, vim });

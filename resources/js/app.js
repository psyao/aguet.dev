/* aguet.dev — Terminal v2 interactions (Alpine.js)
   Adapted from the Claude Design prototype (shared/terminal-v2.js).

   Alpine is NOT bundled here: Filament 5 / Livewire 4 ships Alpine, starts it,
   and exposes window.Alpine. We register our components on the `alpine:init`
   event against that shared instance (no import, no Alpine.start()). The public
   layout loads Livewire's scripts so Alpine is available.

   - Language is server-side (URL /  vs /en); the command palette's
     "switch language" navigates to the alternate URL.
   - The design-tool "Tweaks" panel is removed.
   - Per-locale UI strings + palette data come from window.__AGUET, injected by
     the Blade layout for the current locale only.
   The page is fully functional without this script (progressive enhancement). */

const CFG = window.__AGUET || { locale: 'fr', altUrl: '/en', i18n: {}, projects: [], contact: {} };
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

/* ───────────────── ALPINE COMPONENTS (registered on alpine:init) ───────────────── */
document.addEventListener('alpine:init', () => {
  const Alpine = window.Alpine;

  // Root: kicks off the one-shot boot animation + scrollspy.
  Alpine.data('terminal', () => ({
    init() { boot(); scrollspy(); },
  }));

  // Live clock (Europe/Zurich), tmux-style status bar segment.
  Alpine.data('clock', () => ({
    time: '--:--',
    init() { this.tick(); setInterval(() => this.tick(), 15000); },
    tick() {
      this.time = new Date().toLocaleTimeString(CFG.locale === 'fr' ? 'fr-CH' : 'en-GB', {
        hour: '2-digit', minute: '2-digit', hourCycle: 'h23', timeZone: 'Europe/Zurich',
      });
    },
  }));

  // Click-to-copy contact value with transient "copied" feedback.
  Alpine.data('copy', (value) => ({
    value,
    copied: false,
    label: t('contact.copy', 'copy'),
    _t: null,
    async copyValue() {
      await writeClipboard(this.value);
      this.copied = true;
      this.label = t('contact.copied', 'copied');
      clearTimeout(this._t);
      this._t = setTimeout(() => { this.copied = false; this.label = t('contact.copy', 'copy'); }, 1500);
    },
  }));

  // Email row: the address is base64-encoded in the markup and rebuilt here at
  // runtime, so no plaintext "user@domain" ever sits in the HTML source for
  // naive scrapers to harvest. Without JS the row stays hidden (x-cloak).
  Alpine.data('email', (enc) => ({
    value: '',
    display: '',
    mailto: '#',
    copied: false,
    label: t('contact.copy', 'copy'),
    _t: null,
    init() {
      this.value = deob(enc);
      this.display = '"' + this.value + '"';
      this.mailto = 'mailto:' + this.value;
    },
    async copyValue() {
      await writeClipboard(this.value);
      this.copied = true;
      this.label = t('contact.copied', 'copied');
      clearTimeout(this._t);
      this._t = setTimeout(() => { this.copied = false; this.label = t('contact.copy', 'copy'); }, 1500);
    },
  }));

  // Command palette (⌘K): fuzzy filter, keyboard nav, grouped results.
  Alpine.store('cmdk', {
    isOpen: false,
    query: '',
    active: 0,
    items: [],

    build() {
      const go = (id) => () => { this.close(); const s = document.getElementById(id); if (s) s.scrollIntoView({ behavior: 'smooth' }); };
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

    get filtered() {
      const q = this.query.trim().toLowerCase();
      const list = q ? this.items.filter((c) => (c.label + ' ' + c.hint + ' ' + c.g).toLowerCase().includes(q)) : this.items;
      return list.map((c, i) => ({ ...c, _i: i }));
    },

    // Grouped view (group header + its items) built from the flat filtered list.
    get groups() {
      const out = [];
      let cur = null;
      this.filtered.forEach((it) => {
        if (!cur || cur.g !== it.g) { cur = { g: it.g, items: [] }; out.push(cur); }
        cur.items.push(it);
      });
      return out;
    },

    open() {
      if (!this.items.length) this.build();
      this.query = '';
      this.active = 0;
      this.isOpen = true;
      window.Alpine.nextTick(() => { const i = document.getElementById('cmdk-input'); if (i) i.focus(); });
    },
    close() { this.isOpen = false; },
    toggle() { this.isOpen ? this.close() : this.open(); },
    run(item) { this.close(); item.run(); },
    move(d) {
      const n = this.filtered.length; if (!n) return;
      this.active = (this.active + d + n) % n;
    },
    enter() { const f = this.filtered; if (f[this.active]) this.run(f[this.active]); },
  });

  // Vim command-line echo: ex-commands flash a transient message here, shown
  // in the statusbar (the bottom line, like vim's command-line). Auto-clears.
  Alpine.store('vim', {
    msg: '',
    _t: null,
    flash(m) {
      this.msg = m;
      clearTimeout(this._t);
      this._t = setTimeout(() => { this.msg = ''; }, 2500);
    },
  });

  // Contact modal: accessible dialog shell around the <livewire:contact-form>.
  // Mirrors cmdk but adds what cmdk lacks — a real focus trap, focus return to
  // the trigger, and an inert background — since Livewire's bundled Alpine has
  // no @alpinejs/focus (x-trap) plugin.
  Alpine.store('contact', {
    isOpen: false,
    trigger: null,

    // Tabbable elements inside the panel (visible, not tabindex=-1 → the
    // off-screen honeypot input and the success region are excluded).
    _tabbable(root) {
      const sel = 'a[href],button:not([disabled]),input:not([disabled]),textarea:not([disabled]),select:not([disabled]),[tabindex]';
      return Array.from(root.querySelectorAll(sel)).filter((el) => el.tabIndex >= 0 && el.offsetParent !== null);
    },

    _setBackgroundInert(on) {
      ['.chrome', '.tabs', '#content', '.statusbar', '#cmdk'].forEach((s) => {
        const el = document.querySelector(s);
        if (!el) return;
        on ? el.setAttribute('inert', '') : el.removeAttribute('inert');
      });
    },

    open() {
      if (this.isOpen) return;
      this.trigger = document.activeElement;
      this.isOpen = true;
      this._setBackgroundInert(true);
      document.body.style.overflow = 'hidden';
      window.Alpine.nextTick(() => {
        const panel = document.getElementById('contact-modal-panel');
        if (!panel) return;
        (this._tabbable(panel)[0] || panel).focus();
      });
    },

    // Hide only — the draft is preserved (ESC, backdrop, ✕). Clearing the form
    // is the explicit "annuler" button's job (wire:click="resetForm").
    close() {
      if (!this.isOpen) return;
      this.isOpen = false;
      this._setBackgroundInert(false);
      document.body.style.overflow = '';
      const t = this.trigger;
      this.trigger = null;
      window.Alpine.nextTick(() => { if (t && typeof t.focus === 'function') t.focus(); });
    },

    toggle() { this.isOpen ? this.close() : this.open(); },

    // Wrap Tab focus inside the panel.
    trapFocus(e) {
      const panel = document.getElementById('contact-modal-panel');
      if (!panel) return;
      const f = this._tabbable(panel);
      if (!f.length) { e.preventDefault(); return; }
      const first = f[0];
      const last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    },
  });

  // Statusbar: live vim-mode pill (palette trigger) + commit-popover toggle.
  // The popover CONTENT is server-rendered Blade; this only flips visibility
  // and reflects the current "mode".
  Alpine.data('statusbar', () => ({
    popover: false,
    _sel: false,
    _insert: false,
    _onSel: null, _onFocusIn: null, _onFocusOut: null,

    init() {
      this._onSel = () => {
        const s = window.getSelection();
        const txt = s ? s.toString().trim() : '';
        let inUI = false;
        if (txt && s && s.anchorNode) {
          const el = s.anchorNode.nodeType === 1 ? s.anchorNode : s.anchorNode.parentElement;
          if (el && el.closest('#cmdk, #contact-modal-panel')) inUI = true;
        }
        this._sel = !!txt && !inUI;
      };
      this._onFocusIn = (e) => {
        this._insert = !!(e.target && e.target.closest && e.target.closest('input,textarea,select,[contenteditable]'));
      };
      this._onFocusOut = () => { this._insert = false; };
      document.addEventListener('selectionchange', this._onSel);
      document.addEventListener('focusin', this._onFocusIn);
      document.addEventListener('focusout', this._onFocusOut);
    },

    destroy() {
      document.removeEventListener('selectionchange', this._onSel);
      document.removeEventListener('focusin', this._onFocusIn);
      document.removeEventListener('focusout', this._onFocusOut);
    },

    get mode() {
      const cmdk = window.Alpine && window.Alpine.store('cmdk');
      if (cmdk && cmdk.isOpen) return 'COMMAND';
      if (this._insert) return 'INSERT';
      if (this._sel) return 'VISUAL';
      return 'NORMAL';
    },
  }));
});

// Global shortcuts: ⌘K / Ctrl-K toggles the palette; ':' opens it (vim reflex).
document.addEventListener('keydown', (e) => {
  const cmdk = window.Alpine && window.Alpine.store('cmdk');
  if (!cmdk) return;

  if ((e.key === 'k' || e.key === 'K') && (e.metaKey || e.ctrlKey)) {
    e.preventDefault();
    cmdk.toggle();
    return;
  }

  // ':' is a bare keystroke — ignore while typing or when a layer is already open.
  // shiftKey is allowed (':' needs Shift on many layouts); meta/ctrl/alt are not.
  if (e.key === ':' && !e.metaKey && !e.ctrlKey && !e.altKey) {
    const tgt = e.target;
    if (tgt && tgt.closest && (tgt.matches('input,textarea,select') || tgt.closest('[contenteditable]'))) return;
    const contact = window.Alpine.store('contact');
    if (cmdk.isOpen || (contact && contact.isOpen)) return;
    e.preventDefault();
    cmdk.open();
  }
});

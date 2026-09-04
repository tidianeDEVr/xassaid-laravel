/* Xassaid back-office — comportements génériques (vanilla JS, pas de jQuery).
 *
 *  - Menu latéral mobile            : [data-drawer-toggle], .drawer-backdrop
 *  - Dialogues                      : [data-open="#id"] ouvre <dialog>, [data-close] ferme.
 *      Le déclencheur peut porter data-action="/url" (action du formulaire)
 *      et data-set-<champ>="valeur" pour pré-remplir les champs du formulaire
 *      (input, select, combobox), ainsi que data-modal-title="…".
 *  - Combobox avec recherche         : <select data-search>
 *  - Titre déduit du fichier choisi  : <input type=file data-title-target="#id">
 *  - Aperçu d'image                  : <input type=file data-preview="#img">
 *  - Upload avec progression         : <form data-upload> contenant .upload-state / .upload-error
 *  - Messages flash                  : .flash .close
 */
(function () {
    'use strict';

    const $ = (s, root) => (root || document).querySelector(s);
    const $$ = (s, root) => Array.from((root || document).querySelectorAll(s));

    /* ---------- Menu mobile ---------- */
    const toggleDrawer = (open) => {
        document.body.classList.toggle('drawer-open', open ?? !document.body.classList.contains('drawer-open'));
    };
    $$('[data-drawer-toggle]').forEach((b) => b.addEventListener('click', () => toggleDrawer()));
    const backdrop = $('.drawer-backdrop');
    if (backdrop) backdrop.addEventListener('click', () => toggleDrawer(false));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') toggleDrawer(false); });

    /* ---------- Flash ---------- */
    $$('.flash .close').forEach((b) => b.addEventListener('click', () => b.closest('.flash').remove()));

    /* ---------- Combobox ---------- */
    const enhanceSelect = (select) => {
        if (select.classList.contains('enhanced')) return;
        select.classList.add('enhanced');
        const placeholder = select.dataset.placeholder || (select.options[0] && select.options[0].value === '' ? select.options[0].textContent.trim() : 'Choisir…');
        const wrap = document.createElement('div');
        wrap.className = 'combobox' + (select.classList.contains('sm') ? ' sm' : '');
        wrap.innerHTML = `
            <button type="button" class="combobox-btn" aria-haspopup="listbox" aria-expanded="false">
                <span class="txt"></span><i class="ri-arrow-down-s-line"></i>
            </button>
            <div class="combobox-pop">
                <div class="search"><i class="ri-search-line"></i><input type="text" class="input sm" placeholder="Rechercher…" autocomplete="off"></div>
                <ul class="combobox-list" role="listbox"></ul>
            </div>`;
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);
        const btn = $('.combobox-btn', wrap), txt = $('.txt', btn), search = $('input', wrap), list = $('ul', wrap);
        let hl = -1;

        const options = () => Array.from(select.options).filter((o) => o.value !== '' || select.dataset.allowEmpty !== undefined);
        const label = (o) => o.textContent.trim();

        const sync = () => {
            const o = select.options[select.selectedIndex];
            const empty = !o || o.value === '';
            txt.textContent = empty ? placeholder : label(o);
            txt.classList.toggle('placeholder', empty);
        };

        const render = () => {
            const q = search.value.trim().toLowerCase();
            list.innerHTML = '';
            let n = 0;
            if (select.dataset.allowEmpty !== undefined || (select.options[0] && select.options[0].value === '')) {
                if (!q) {
                    const li = document.createElement('li');
                    li.dataset.value = '';
                    li.innerHTML = `<span class="muted">${escapeHtml(select.options[0].textContent.trim() || placeholder)}</span>`;
                    if (select.value === '') li.classList.add('sel');
                    list.appendChild(li); n++;
                }
            }
            Array.from(select.options).forEach((o) => {
                if (o.value === '') return;
                const grp = o.dataset.group || '';
                const hay = (grp + ' ' + label(o)).toLowerCase();
                if (q && !hay.includes(q)) return;
                const li = document.createElement('li');
                li.dataset.value = o.value;
                li.innerHTML = (grp ? `<span class="grp">${escapeHtml(grp)}</span>` : '') + escapeHtml(label(o));
                if (o.selected) li.classList.add('sel');
                list.appendChild(li); n++;
            });
            if (!n) { list.innerHTML = '<li class="none">Aucun résultat</li>'; }
            hl = -1;
        };

        const pop = $('.combobox-pop', wrap);
        // La liste est positionnée en fixe par rapport à la fenêtre : elle
        // n'est ainsi jamais rognée ni « scrollée » par le corps d'une modale.
        const place = () => {
            const r = btn.getBoundingClientRect();
            const vh = window.innerHeight || document.documentElement.clientHeight;
            const below = vh - r.bottom - 12, above = r.top - 12;
            const useAbove = below < 220 && above > below;
            const room = useAbove ? above : below;
            pop.style.left = r.left + 'px';
            pop.style.width = r.width + 'px';
            list.style.maxHeight = Math.max(120, Math.min(260, room - 56)) + 'px';
            if (useAbove) { pop.style.top = 'auto'; pop.style.bottom = (vh - r.top + 4) + 'px'; }
            else { pop.style.bottom = 'auto'; pop.style.top = (r.bottom + 4) + 'px'; }
        };
        const onScroll = (e) => { if (!pop.contains(e.target)) place(); };
        const open = () => {
            $$('.combobox.open').forEach((c) => c !== wrap && close(c));
            wrap.classList.add('open'); btn.setAttribute('aria-expanded', 'true');
            search.value = ''; render(); place();
            window.addEventListener('scroll', onScroll, true);
            window.addEventListener('resize', place);
            setTimeout(() => search.focus({ preventScroll: true }), 0);
        };
        const close = (w = wrap) => {
            w.classList.remove('open'); $('.combobox-btn', w).setAttribute('aria-expanded', 'false');
            window.removeEventListener('scroll', onScroll, true);
            window.removeEventListener('resize', place);
        };
        const choose = (value) => { select.value = value; select.dispatchEvent(new Event('change', { bubbles: true })); sync(); close(); btn.focus(); };

        btn.addEventListener('click', () => wrap.classList.contains('open') ? close() : open());
        search.addEventListener('input', render);
        list.addEventListener('click', (e) => { const li = e.target.closest('li'); if (li && !li.classList.contains('none')) choose(li.dataset.value); });
        search.addEventListener('keydown', (e) => {
            const items = $$('li:not(.none)', list);
            if (e.key === 'ArrowDown') { e.preventDefault(); hl = Math.min(hl + 1, items.length - 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); hl = Math.max(hl - 1, 0); }
            else if (e.key === 'Enter') { e.preventDefault(); const it = items[hl] || items[0]; if (it) choose(it.dataset.value); return; }
            else if (e.key === 'Escape') { close(); btn.focus(); return; }
            else return;
            items.forEach((li, i) => li.classList.toggle('hl', i === hl));
            if (items[hl]) items[hl].scrollIntoView({ block: 'nearest' });
        });
        select.addEventListener('change', sync);
        select.addEventListener('combobox:sync', sync);
        sync();
    };
    document.addEventListener('click', (e) => { if (!e.target.closest('.combobox')) $$('.combobox.open').forEach((c) => c.classList.remove('open')); });
    $$('select[data-search]').forEach(enhanceSelect);
    window.enhanceSelect = enhanceSelect;

    /* ---------- Dialogues ---------- */
    const setField = (form, name, value) => {
        const el = form.elements[name];
        if (!el) return;
        if (el.type === 'file') return;
        el.value = value ?? '';
        if (el.tagName === 'SELECT') el.dispatchEvent(new Event('combobox:sync'));
    };
    const openDialog = (dlg, trigger) => {
        if (!dlg) return;
        const form = $('form', dlg);
        if (trigger && form) {
            if (trigger.dataset.action) form.action = trigger.dataset.action;
            Object.keys(trigger.dataset).forEach((k) => {
                if (k.startsWith('set') && k.length > 3) {
                    const name = k.slice(3).replace(/^[A-Z]/, (c) => c.toLowerCase()).replace(/[A-Z]/g, (c) => '_' + c.toLowerCase());
                    setField(form, name, trigger.dataset[k]);
                }
            });
        }
        if (trigger && trigger.dataset.modalTitle) { const h = $('.modal-head h2', dlg); if (h) h.textContent = trigger.dataset.modalTitle; }
        dlg.dispatchEvent(new CustomEvent('modal:open', { detail: { trigger } }));
        dlg.showModal();
        const first = $('input:not([type=hidden]):not([type=file]), textarea, select', dlg);
        if (first && !first.classList.contains('enhanced')) setTimeout(() => first.focus(), 30);
    };
    document.addEventListener('click', (e) => {
        const t = e.target.closest('[data-open]');
        if (t) { e.preventDefault(); openDialog($(t.dataset.open), t); return; }
        const c = e.target.closest('[data-close]');
        if (c) { e.preventDefault(); const d = c.closest('dialog'); if (d) d.close(); }
    });
    $$('dialog.modal').forEach((dlg) => {
        dlg.addEventListener('click', (e) => { if (e.target === dlg) dlg.close(); });
        dlg.addEventListener('close', () => dlg.dispatchEvent(new CustomEvent('modal:close')));
    });
    window.openDialog = openDialog;

    /* ---------- Titre déduit du nom de fichier ---------- */
    const titleFromFilename = (name) => name.replace(/\.[^/.]+$/, '').replace(/[-_]+/g, ' ').trim()
        .split(/\s+/).map((w) => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ');
    $$('input[type=file][data-title-target]').forEach((input) => {
        input.addEventListener('change', () => {
            const target = $(input.dataset.titleTarget);
            if (target && input.files.length && (!target.value || input.dataset.titleForce !== undefined)) target.value = titleFromFilename(input.files[0].name);
        });
    });

    /* ---------- Aperçu d'image ---------- */
    $$('input[type=file][data-preview]').forEach((input) => {
        input.addEventListener('change', () => {
            const img = $(input.dataset.preview);
            if (!img) return;
            const file = input.files && input.files[0];
            if (img.src && img.src.startsWith('blob:')) URL.revokeObjectURL(img.src);
            img.src = file ? URL.createObjectURL(file) : '';
            img.style.display = file ? '' : 'none';
        });
    });

    /* ---------- Upload avec barre de progression ---------- */
    $$('form[data-upload]').forEach((form) => {
        const state = $('.upload-state', form), bar = $('.progress > span', form), txt = $('.upload-state .txt', form), err = $('.upload-error', form);
        const buttons = $$('button', form);
        const reset = () => {
            if (state) state.style.display = 'none';
            if (bar) { bar.style.width = '0%'; bar.classList.remove('err', 'busy'); }
            if (err) { err.style.display = 'none'; err.textContent = ''; }
            buttons.forEach((b) => b.disabled = false);
        };
        const dlg = form.closest('dialog');
        if (dlg) dlg.addEventListener('modal:close', reset);
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            reset();
            if (state) state.style.display = 'block';
            if (bar) bar.classList.add('busy');
            buttons.forEach((b) => b.disabled = true);
            const xhr = new XMLHttpRequest();
            xhr.open(form.method || 'POST', form.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.upload.onprogress = (ev) => {
                if (!ev.lengthComputable) return;
                const p = Math.round(100 * ev.loaded / ev.total);
                if (bar) bar.style.width = p + '%';
                if (txt) txt.textContent = p < 100 ? `Envoi du fichier… ${p} %` : 'Fichier reçu, traitement en cours…';
            };
            xhr.onload = () => {
                if (xhr.status === 200 || xhr.status === 302) {
                    if (txt) txt.textContent = 'Terminé, rechargement…';
                    if (bar) bar.classList.remove('busy');
                    setTimeout(() => window.location.reload(), 400);
                    return;
                }
                let message = `Erreur inattendue (HTTP ${xhr.status})`;
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.error) message = res.error;
                    else if (res.errors) message = Object.values(res.errors).flat().join('\n');
                    else if (res.message) message = res.message;
                } catch (_) { if (xhr.responseText) message += ' : ' + xhr.responseText.substring(0, 300); }
                fail(message);
            };
            xhr.onerror = () => fail('Erreur réseau : impossible de joindre le serveur.');
            const fail = (message) => {
                if (bar) { bar.classList.remove('busy'); bar.classList.add('err'); bar.style.width = '100%'; }
                if (txt) txt.textContent = 'Échec de l\'envoi';
                if (err) { err.textContent = message; err.style.display = 'flex'; }
                buttons.forEach((b) => b.disabled = false);
            };
            xhr.send(new FormData(form));
        });
    });

    function escapeHtml(s) { return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c])); }
    window.escapeHtml = escapeHtml;
})();

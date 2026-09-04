@extends('base')

@section('title', 'Santé des fichiers')

@section('content')
@php
    // Outils de vérification : chacun scanne le catalogue par lots, sans
    // télécharger les fichiers, et propose la suppression des entrées cassées.
    $scanTools = [
        ['audios', 'Audios manquants', 'Vérifie que chaque MP3 répond encore sur le serveur de fichiers.', $audioBase, '/audios'],
        ['files', 'PDF manquants', 'Vérifie que chaque document de la bibliothèque répond encore.', $fileBase, '/library'],
    ];
    $dupTools = [
        ['audios', 'Doublons d\'audios', '/audios'],
        ['files', 'Doublons de PDF', '/library'],
    ];
@endphp
<div class="page">
    <div class="page-head">
        <div>
            <h1>Santé des fichiers</h1>
            <div class="sub">Vérification par lots des médias distants, sans téléchargement. Les entrées cassées peuvent être supprimées directement.</div>
        </div>
    </div>

    @include('partials.flash')

    @foreach ($scanTools as [$type, $title, $desc, $base, $deleteBase])
        <div class="card mb-3" data-analytics="{{ $type }}" data-delete-base="{{ $deleteBase }}">
            <div class="card-head">
                <div><div>{{ $title }}</div><div class="hint">{{ $desc }} @if ($base === '')<span style="color:var(--danger)">Adresse de base non configurée.</span>@endif</div></div>
            </div>
            <div class="toolbar">
                <label class="label" for="limit-{{ $type }}">Taille du lot</label>
                <input type="number" class="input sm w-auto" id="limit-{{ $type }}" value="30" min="1" max="100" style="width:90px;flex:none;min-width:0" data-limit>
                <button class="btn btn-sm btn-primary" type="button" data-scan-reset><i class="ri-play-line"></i> Scanner</button>
                <button class="btn btn-sm" type="button" data-scan-next>Lot suivant</button>
                <span class="result" data-stats>Vérifiés <b data-checked>0</b> · Manquants <b data-missing>0</b> · Dernier ID <b data-cursor>0</b> <span data-status></span></span>
            </div>
            <div class="bulk-bar hidden" data-bulk>
                <label class="check"><input type="checkbox" data-check-all> <span data-bulk-count>0</span> sélectionné(s)</label>
                <button class="btn btn-sm btn-danger" type="button" data-bulk-delete><i class="ri-delete-bin-6-line"></i> Supprimer la sélection</button>
                <span class="result" data-bulk-status></span>
            </div>
            <div class="table-wrap">
                <table class="table stack">
                    <thead><tr><th class="chk"><input type="checkbox" data-check-all aria-label="Tout sélectionner"></th><th>ID</th><th>Titre</th><th>URL</th><th>Statut</th><th></th></tr></thead>
                    <tbody data-results><tr class="empty-row" data-placeholder="true"><td colspan="6">Aucun scan lancé.</td></tr></tbody>
                </table>
            </div>
        </div>
    @endforeach

    <div class="card mb-3" data-short-audios="audios" data-delete-base="/audios">
        <div class="card-head">
            <div><div>Audios trop courts</div><div class="hint">Repère les fichiers dont la durée est inférieure au seuil (probablement tronqués ou vides).</div></div>
        </div>
        <div class="toolbar">
            <label class="label" for="shortLimit">Taille du lot</label>
            <input type="number" class="input sm w-auto" id="shortLimit" value="20" min="1" max="50" style="width:90px;flex:none;min-width:0" data-short-limit>
            <label class="label" for="shortMax">Durée max (s)</label>
            <input type="number" class="input sm w-auto" id="shortMax" value="60" min="10" max="600" style="width:90px;flex:none;min-width:0" data-short-max>
            <button class="btn btn-sm btn-primary" type="button" data-short-reset><i class="ri-play-line"></i> Scanner</button>
            <button class="btn btn-sm" type="button" data-short-next>Lot suivant</button>
            <span class="result">Vérifiés <b data-short-checked>0</b> · Courts <b data-short-count>0</b> · Ignorés (gros fichiers) <b data-short-skipped>0</b> · Inconnus <b data-short-unknown>0</b> · Dernier ID <b data-short-cursor>0</b> <span data-status></span></span>
        </div>
        <div class="bulk-bar hidden" data-bulk>
            <label class="check"><input type="checkbox" data-check-all> <span data-bulk-count>0</span> sélectionné(s)</label>
            <button class="btn btn-sm btn-danger" type="button" data-bulk-delete><i class="ri-delete-bin-6-line"></i> Supprimer la sélection</button>
            <span class="result" data-bulk-status></span>
        </div>
        <div class="table-wrap">
            <table class="table stack">
                <thead><tr><th class="chk"><input type="checkbox" data-check-all aria-label="Tout sélectionner"></th><th>ID</th><th>Titre</th><th>Durée</th><th>URL</th><th></th></tr></thead>
                <tbody data-short-results><tr class="empty-row" data-placeholder="true"><td colspan="6">Aucun scan lancé.</td></tr></tbody>
            </table>
        </div>
    </div>

    @foreach ($dupTools as [$type, $title, $deleteBase])
        <div class="card mb-3" data-duplicates="{{ $type }}" data-delete-base="{{ $deleteBase }}">
            <div class="card-head">
                <div><div>{{ $title }}</div><div class="hint">Regroupe les titres identiques (normalisés) ou proches (mêmes mots).</div></div>
            </div>
            <div class="toolbar">
                <label class="label" for="dupMode-{{ $type }}">Mode</label>
                <select class="select sm w-auto" id="dupMode-{{ $type }}" style="flex:none;min-width:0" data-duplicate-mode>
                    <option value="exact">Exact (normalisé)</option>
                    <option value="similar">Similaire (mots)</option>
                </select>
                <label class="label" for="dupLimit-{{ $type }}">Résultats</label>
                <input type="number" class="input sm w-auto" id="dupLimit-{{ $type }}" value="50" min="1" max="200" style="width:90px;flex:none;min-width:0" data-duplicate-limit>
                <button class="btn btn-sm btn-primary" type="button" data-duplicate-run><i class="ri-play-line"></i> Analyser</button>
                <span class="result">Groupes <b data-duplicate-total>0</b> <span data-status></span></span>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Clé</th><th class="right">Occurrences</th><th>Éléments</th></tr></thead>
                    <tbody data-duplicate-results><tr class="empty-row" data-placeholder="true"><td colspan="3">Aucune analyse lancée.</td></tr></tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
@endsection

@section('scripts')
<script>
    const csrfToken = "{{ csrf_token() }}";
    const BASE = '/file-health';
    const escapeHtml = window.escapeHtml;

    // Suppression sans rechargement : la ligne disparaît, le scan reste affiché.
    const deleteButton = (id) => `<button class="btn btn-sm btn-danger btn-icon" type="button" data-delete="${id}" title="Supprimer"><i class="ri-delete-bin-6-line"></i></button>`;
    const checkbox = (id) => `<input type="checkbox" data-pick value="${id}" aria-label="Sélectionner">`;

    async function deleteItem(deleteBase, id) {
        const r = await fetch(`${deleteBase}/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
    }

    function removeRow(section, id) {
        section.querySelectorAll(`[data-pick][value="${id}"]`).forEach((cb) => {
            const row = cb.closest('tr');
            const holder = cb.closest('.dup-item');
            if (holder) holder.remove(); else if (row) row.remove();
        });
        section.querySelectorAll(`[data-delete="${id}"]`).forEach((b) => { const row = b.closest('tr'); const holder = b.closest('.dup-item'); if (holder) holder.remove(); else if (row) row.remove(); });
        const body = section.querySelector('[data-results], [data-short-results]');
        if (body && !body.querySelector('tr')) body.innerHTML = placeholderRow(6, 'Plus rien à corriger dans ce lot.');
        updateBulk(section);
    }

    function updateBulk(section) {
        const bar = section.querySelector('[data-bulk]');
        if (!bar) return;
        const picks = [...section.querySelectorAll('[data-pick]')];
        const n = picks.filter((c) => c.checked).length;
        bar.classList.toggle('hidden', picks.length === 0);
        section.querySelector('[data-bulk-count]').textContent = n;
        section.querySelector('[data-bulk-delete]').disabled = n === 0;
        section.querySelectorAll('[data-check-all]').forEach((a) => { a.checked = picks.length > 0 && n === picks.length; a.indeterminate = n > 0 && n < picks.length; });
    }

    document.addEventListener('change', (e) => {
        const section = e.target.closest('.card');
        if (!section) return;
        if (e.target.matches('[data-check-all]')) {
            section.querySelectorAll('[data-pick]').forEach((c) => c.checked = e.target.checked);
        }
        if (e.target.matches('[data-check-all], [data-pick]')) updateBulk(section);
    });

    document.addEventListener('click', async (e) => {
        const one = e.target.closest('[data-delete]');
        const bulk = e.target.closest('[data-bulk-delete]');
        if (!one && !bulk) return;
        const section = e.target.closest('.card');
        const deleteBase = section.getAttribute('data-delete-base');
        const ids = one ? [one.dataset.delete] : [...section.querySelectorAll('[data-pick]:checked')].map((c) => c.value);
        if (!ids.length) return;
        if (!confirm(ids.length === 1 ? 'Supprimer cet élément ?' : `Supprimer ${ids.length} éléments ? Cette action est irréversible.`)) return;
        const status = section.querySelector('[data-bulk-status]');
        let done = 0, failed = 0;
        for (const id of ids) {
            if (status) status.textContent = `Suppression ${done + failed + 1}/${ids.length}…`;
            try { await deleteItem(deleteBase, id); removeRow(section, id); done++; }
            catch (err) { failed++; }
        }
        if (status) status.textContent = failed ? `${done} supprimé(s), ${failed} échec(s).` : `${done} supprimé(s).`;
    });
    const link = (url, label) => url ? `<a href="${escapeHtml(url)}" target="_blank" rel="noopener">${escapeHtml(label || url)}</a>` : '—';

    function buildRow(item, deleteBase) {
        const status = item.status !== null && item.status !== undefined ? item.status : '—';
        return `<tr>
            <td class="chk">${checkbox(item.id)}</td>
            <td data-label="ID" class="muted">${item.id}</td>
            <td data-label="Titre">${escapeHtml(item.title || '')}</td>
            <td data-label="URL" class="clip small">${link(item.url)}</td>
            <td data-label="Statut"><span class="badge err">${escapeHtml(status)}</span>${item.reason ? ' <span class="small muted">' + escapeHtml(item.reason) + '</span>' : ''}</td>
            <td class="actions-cell">${deleteButton(item.id)}</td>
        </tr>`;
    }

    function buildShortRow(item, deleteBase) {
        return `<tr>
            <td class="chk">${checkbox(item.id)}</td>
            <td data-label="ID" class="muted">${item.id}</td>
            <td data-label="Titre">${escapeHtml(item.title || '')}</td>
            <td data-label="Durée">${escapeHtml(item.durationLabel || '—')}</td>
            <td data-label="URL" class="clip small">${link(item.url)}</td>
            <td class="actions-cell">${deleteButton(item.id)}</td>
        </tr>`;
    }

    function buildDuplicateRow(group, deleteBase) {
        const items = (group.items || []).map((item) => `
            <div class="row dup-item" style="justify-content:space-between;padding:4px 0;border-bottom:1px dashed var(--line)">
                <div><span class="muted">#${item.id}</span> ${escapeHtml(item.title || '')} ${item.url ? '· ' + link(item.url, 'ouvrir') : ''}</div>
                ${deleteButton(item.id)}
            </div>`).join('');
        return `<tr><td>${escapeHtml(group.key)}</td><td class="right">${group.count}</td><td>${items || '—'}</td></tr>`;
    }

    function updateStatus(section, message, isError) {
        const statusEl = section.querySelector('[data-status]');
        if (!statusEl) return;
        statusEl.textContent = message ? '· ' + message : '';
        statusEl.style.color = isError ? 'var(--danger)' : '';
    }

    const fetchJson = (url) => fetch(url, { headers: { 'Accept': 'application/json' } }).then((r) => r.json());
    const placeholderRow = (cols, text) => `<tr class="empty-row" data-placeholder="true"><td colspan="${cols}">${escapeHtml(text)}</td></tr>`;

    function scan(type, reset) {
        const section = document.querySelector(`[data-analytics="${type}"]`);
        const resultsEl = section.querySelector('[data-results]');
        const checkedEl = section.querySelector('[data-checked]');
        const missingEl = section.querySelector('[data-missing]');
        const cursorEl = section.querySelector('[data-cursor]');
        const limit = Math.min(Math.max(parseInt(section.querySelector('[data-limit]').value || '30', 10), 1), 100);
        const deleteBase = section.getAttribute('data-delete-base');
        const currentCursor = reset ? 0 : parseInt(cursorEl.textContent || '0', 10);
        if (reset) { resultsEl.innerHTML = ''; checkedEl.textContent = '0'; missingEl.textContent = '0'; cursorEl.textContent = '0'; }
        updateStatus(section, 'Scan en cours…', false);

        fetchJson(`${BASE}/check?type=${type}&cursor=${currentCursor}&limit=${limit}`)
            .then((data) => {
                if (data.message) {
                    updateStatus(section, data.message, true);
                    if (!resultsEl.children.length) resultsEl.innerHTML = placeholderRow(6, data.message);
                    return;
                }
                checkedEl.textContent = parseInt(checkedEl.textContent || '0', 10) + (data.checked || 0);
                missingEl.textContent = parseInt(missingEl.textContent || '0', 10) + (data.missing || 0);
                cursorEl.textContent = data.nextCursor || currentCursor;
                if (!data.items || data.items.length === 0) {
                    if (!resultsEl.querySelector('tr') || resultsEl.querySelector('[data-placeholder]')) resultsEl.innerHTML = placeholderRow(6, 'Aucun fichier manquant dans ce lot.');
                } else {
                    resultsEl.querySelector('[data-placeholder]')?.remove();
                    data.items.forEach((item) => resultsEl.insertAdjacentHTML('beforeend', buildRow(item, deleteBase)));
                }
                updateBulk(section);
                updateStatus(section, data.done ? 'Scan terminé.' : 'Lot terminé, prêt pour le suivant.', false);
            })
            .catch(() => updateStatus(section, 'Erreur réseau lors du scan.', true));
    }

    function scanShortAudios(reset) {
        const section = document.querySelector('[data-short-audios="audios"]');
        const resultsEl = section.querySelector('[data-short-results]');
        const els = ['checked', 'count', 'skipped', 'unknown', 'cursor'].reduce((acc, k) => (acc[k] = section.querySelector(`[data-short-${k}]`), acc), {});
        const limit = Math.min(Math.max(parseInt(section.querySelector('[data-short-limit]').value || '20', 10), 1), 50);
        const maxDuration = Math.min(Math.max(parseInt(section.querySelector('[data-short-max]').value || '60', 10), 10), 600);
        const deleteBase = section.getAttribute('data-delete-base');
        const currentCursor = reset ? 0 : parseInt(els.cursor.textContent || '0', 10);
        if (reset) { resultsEl.innerHTML = ''; Object.values(els).forEach((el) => el.textContent = '0'); }
        updateStatus(section, 'Scan en cours…', false);

        fetchJson(`${BASE}/short-audios?cursor=${currentCursor}&limit=${limit}&maxDuration=${maxDuration}`)
            .then((data) => {
                if (data.message) {
                    updateStatus(section, data.message, true);
                    if (!resultsEl.children.length) resultsEl.innerHTML = placeholderRow(6, data.message);
                    return;
                }
                const add = (el, n) => el.textContent = parseInt(el.textContent || '0', 10) + (n || 0);
                add(els.checked, data.checked); add(els.count, data.short); add(els.skipped, data.skippedLarge); add(els.unknown, data.unknown);
                els.cursor.textContent = data.nextCursor || currentCursor;
                if (!data.items || data.items.length === 0) {
                    if (!resultsEl.querySelector('tr') || resultsEl.querySelector('[data-placeholder]')) resultsEl.innerHTML = placeholderRow(6, 'Aucun audio court dans ce lot.');
                } else {
                    resultsEl.querySelector('[data-placeholder]')?.remove();
                    data.items.forEach((item) => resultsEl.insertAdjacentHTML('beforeend', buildShortRow(item, deleteBase)));
                }
                updateBulk(section);
                updateStatus(section, data.done ? 'Scan terminé.' : 'Lot terminé, prêt pour le suivant.', false);
            })
            .catch(() => updateStatus(section, 'Erreur réseau lors du scan.', true));
    }

    function runDuplicates(type) {
        const section = document.querySelector(`[data-duplicates="${type}"]`);
        const resultsEl = section.querySelector('[data-duplicate-results]');
        const totalEl = section.querySelector('[data-duplicate-total]');
        const mode = section.querySelector('[data-duplicate-mode]').value || 'exact';
        const limit = Math.min(Math.max(parseInt(section.querySelector('[data-duplicate-limit]').value || '50', 10), 1), 200);
        const deleteBase = section.getAttribute('data-delete-base');
        updateStatus(section, 'Analyse en cours…', false);

        fetchJson(`${BASE}/duplicates?type=${type}&mode=${mode}&limit=${limit}`)
            .then((data) => {
                if (data.message) {
                    updateStatus(section, data.message, true);
                    if (!resultsEl.children.length) resultsEl.innerHTML = placeholderRow(3, data.message);
                    return;
                }
                totalEl.textContent = data.total || 0;
                resultsEl.innerHTML = (!data.groups || data.groups.length === 0)
                    ? placeholderRow(3, 'Aucun doublon détecté.')
                    : data.groups.map((g) => buildDuplicateRow(g, deleteBase)).join('');
                updateStatus(section, 'Analyse terminée.', false);
            })
            .catch(() => updateStatus(section, 'Erreur réseau lors de l\'analyse.', true));
    }

    document.querySelectorAll('[data-analytics]').forEach((section) => {
        const type = section.getAttribute('data-analytics');
        section.querySelector('[data-scan-reset]').addEventListener('click', () => scan(type, true));
        section.querySelector('[data-scan-next]').addEventListener('click', () => scan(type, false));
    });
    const shortSection = document.querySelector('[data-short-audios="audios"]');
    shortSection.querySelector('[data-short-reset]').addEventListener('click', () => scanShortAudios(true));
    shortSection.querySelector('[data-short-next]').addEventListener('click', () => scanShortAudios(false));
    document.querySelectorAll('[data-duplicates]').forEach((section) => {
        section.querySelector('[data-duplicate-run]').addEventListener('click', () => runDuplicates(section.getAttribute('data-duplicates')));
    });
</script>
@endsection

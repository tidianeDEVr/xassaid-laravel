@extends('base')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-3 gap-2">
        <h1>Analytics</h1>
        <div class="text-muted">Scan par lots, sans télécharger les fichiers</div>
    </div>

    <div class="row g-4">
        <div class="col-12" data-analytics="audios" data-delete-base="/audios">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
                        <h2 class="h5 mb-0">Audios</h2>
                        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2">
                            <label class="form-label mb-0" for="audioLimit">Taille du lot</label>
                            <input type="number" class="form-control" id="audioLimit" value="30" min="1" max="100" style="width: 120px" data-limit>
                            <button class="btn btn-outline-primary" type="button" data-scan-reset>Scanner</button>
                            <button class="btn btn-outline-secondary" type="button" data-scan-next>Scanner suivant</button>
                        </div>
                    </div>
                    <div class="small text-muted mt-2">Base URL: {{ $audioBase !== '' ? $audioBase : 'Non configurée' }}</div>
                    <div class="mt-3 d-flex flex-column flex-md-row gap-2 text-muted" data-stats>
                        <span>Vérifiés: <strong data-checked>0</strong></span>
                        <span>Manquants: <strong data-missing>0</strong></span>
                        <span>Dernier ID: <strong data-cursor>0</strong></span>
                        <span data-status class="ms-md-auto"></span>
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Titre</th>
                                    <th>URL</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody data-results>
                                <tr class="text-muted" data-placeholder="true">
                                    <td colspan="5">Aucun scan lancé.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12" data-analytics="files" data-delete-base="/library">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
                        <h2 class="h5 mb-0">Bibliothèque (PDF)</h2>
                        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2">
                            <label class="form-label mb-0" for="fileLimit">Taille du lot</label>
                            <input type="number" class="form-control" id="fileLimit" value="30" min="1" max="100" style="width: 120px" data-limit>
                            <button class="btn btn-outline-primary" type="button" data-scan-reset>Scanner</button>
                            <button class="btn btn-outline-secondary" type="button" data-scan-next>Scanner suivant</button>
                        </div>
                    </div>
                    <div class="small text-muted mt-2">Base URL: {{ $fileBase !== '' ? $fileBase : 'Non configurée' }}</div>
                    <div class="mt-3 d-flex flex-column flex-md-row gap-2 text-muted" data-stats>
                        <span>Vérifiés: <strong data-checked>0</strong></span>
                        <span>Manquants: <strong data-missing>0</strong></span>
                        <span>Dernier ID: <strong data-cursor>0</strong></span>
                        <span data-status class="ms-md-auto"></span>
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Titre</th>
                                    <th>URL</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody data-results>
                                <tr class="text-muted" data-placeholder="true">
                                    <td colspan="5">Aucun scan lancé.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12" data-short-audios="audios" data-delete-base="/audios">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
                        <h2 class="h5 mb-0">Audios courts</h2>
                        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2">
                            <label class="form-label mb-0" for="shortLimit">Taille du lot</label>
                            <input type="number" class="form-control" id="shortLimit" value="20" min="1" max="50" style="width: 120px" data-short-limit>
                            <label class="form-label mb-0" for="shortMax">Durée max (sec)</label>
                            <input type="number" class="form-control" id="shortMax" value="60" min="10" max="600" style="width: 120px" data-short-max>
                            <button class="btn btn-outline-primary" type="button" data-short-reset>Scanner</button>
                            <button class="btn btn-outline-secondary" type="button" data-short-next>Scanner suivant</button>
                        </div>
                    </div>
                    <div class="small text-muted mt-2">Base URL: {{ $audioBase !== '' ? $audioBase : 'Non configurée' }}</div>
                    <div class="mt-3 d-flex flex-column flex-md-row gap-2 text-muted">
                        <span>Vérifiés: <strong data-short-checked>0</strong></span>
                        <span>Courts: <strong data-short-count>0</strong></span>
                        <span>Ignorés (gros fichiers): <strong data-short-skipped>0</strong></span>
                        <span>Inconnus: <strong data-short-unknown>0</strong></span>
                        <span>Dernier ID: <strong data-short-cursor>0</strong></span>
                        <span data-status class="ms-md-auto"></span>
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Titre</th>
                                    <th>Durée</th>
                                    <th>URL</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody data-short-results>
                                <tr class="text-muted" data-placeholder="true">
                                    <td colspan="5">Aucun scan lancé.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12" data-duplicates="audios" data-delete-base="/audios">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
                        <h2 class="h5 mb-0">Doublons / Similaires (Audios)</h2>
                        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2">
                            <label class="form-label mb-0" for="audioDuplicateMode">Mode</label>
                            <select class="form-select" id="audioDuplicateMode" style="width: 180px" data-duplicate-mode>
                                <option value="exact">Exact (normalisé)</option>
                                <option value="similar">Similaire (mots)</option>
                            </select>
                            <label class="form-label mb-0" for="audioDuplicateLimit">Résultats</label>
                            <input type="number" class="form-control" id="audioDuplicateLimit" value="50" min="1" max="200" style="width: 120px" data-duplicate-limit>
                            <button class="btn btn-outline-primary" type="button" data-duplicate-run>Analyser</button>
                        </div>
                    </div>
                    <div class="mt-3 d-flex flex-column flex-md-row gap-2 text-muted">
                        <span>Groupes: <strong data-duplicate-total>0</strong></span>
                        <span data-status class="ms-md-auto"></span>
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Clé</th>
                                    <th>Occurrences</th>
                                    <th>Éléments</th>
                                </tr>
                            </thead>
                            <tbody data-duplicate-results>
                                <tr class="text-muted" data-placeholder="true">
                                    <td colspan="3">Aucune analyse lancée.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12" data-duplicates="files" data-delete-base="/library">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
                        <h2 class="h5 mb-0">Doublons / Similaires (Bibliothèque)</h2>
                        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2">
                            <label class="form-label mb-0" for="fileDuplicateMode">Mode</label>
                            <select class="form-select" id="fileDuplicateMode" style="width: 180px" data-duplicate-mode>
                                <option value="exact">Exact (normalisé)</option>
                                <option value="similar">Similaire (mots)</option>
                            </select>
                            <label class="form-label mb-0" for="fileDuplicateLimit">Résultats</label>
                            <input type="number" class="form-control" id="fileDuplicateLimit" value="50" min="1" max="200" style="width: 120px" data-duplicate-limit>
                            <button class="btn btn-outline-primary" type="button" data-duplicate-run>Analyser</button>
                        </div>
                    </div>
                    <div class="mt-3 d-flex flex-column flex-md-row gap-2 text-muted">
                        <span>Groupes: <strong data-duplicate-total>0</strong></span>
                        <span data-status class="ms-md-auto"></span>
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Clé</th>
                                    <th>Occurrences</th>
                                    <th>Éléments</th>
                                </tr>
                            </thead>
                            <tbody data-duplicate-results>
                                <tr class="text-muted" data-placeholder="true">
                                    <td colspan="3">Aucune analyse lancée.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const csrfToken = "{{ csrf_token() }}";

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function buildRow(item, deleteBase) {
        const url = item.url ? `<a href="${item.url}" target="_blank">${item.url}</a>` : "-";
        const status = item.status !== null && item.status !== undefined ? item.status : "-";
        return `
            <tr>
                <td>${item.id}</td>
                <td>${escapeHtml(item.title || '')}</td>
                <td>${url}</td>
                <td>${status}${item.reason ? ' - ' + escapeHtml(item.reason) : ''}</td>
                <td>
                    <form method="post" action="${deleteBase}/${item.id}" onsubmit="return confirm('Confirmer la suppression ?')">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="delete">
                        <button class="btn btn-sm btn-outline-danger" type="submit">
                            <i class="ri-delete-bin-6-line"></i>
                        </button>
                    </form>
                </td>
            </tr>
        `;
    }

    function buildShortRow(item, deleteBase) {
        const url = item.url ? `<a href="${item.url}" target="_blank">${item.url}</a>` : "-";
        const duration = item.durationLabel || '-';
        return `
            <tr>
                <td>${item.id}</td>
                <td>${escapeHtml(item.title || '')}</td>
                <td>${duration}</td>
                <td>${url}</td>
                <td>
                    <form method="post" action="${deleteBase}/${item.id}" onsubmit="return confirm('Confirmer la suppression ?')">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="delete">
                        <button class="btn btn-sm btn-outline-danger" type="submit">
                            <i class="ri-delete-bin-6-line"></i>
                        </button>
                    </form>
                </td>
            </tr>
        `;
    }

    function buildDuplicateRow(group, deleteBase) {
        const items = (group.items || []).map(item => {
            const url = item.url ? `<a href="${item.url}" target="_blank">ouvrir</a>` : '';
            return `
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 border-bottom py-1">
                    <div>
                        <span class="text-muted">#${item.id}</span>
                        ${escapeHtml(item.title || '')}
                        ${url ? ' - ' + url : ''}
                    </div>
                    <form method="post" action="${deleteBase}/${item.id}" onsubmit="return confirm('Confirmer la suppression ?')">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="delete">
                        <button class="btn btn-sm btn-outline-danger" type="submit">
                            <i class="ri-delete-bin-6-line"></i>
                        </button>
                    </form>
                </div>
            `;
        }).join('');

        return `
            <tr>
                <td>${escapeHtml(group.key)}</td>
                <td>${group.count}</td>
                <td>${items || '-'}</td>
            </tr>
        `;
    }

    function updateStatus(section, message, isError) {
        const statusEl = section.querySelector('[data-status]');
        if (!statusEl) {
            return;
        }
        statusEl.textContent = message;
        statusEl.classList.toggle('text-danger', !!isError);
        statusEl.classList.toggle('text-muted', !isError);
    }

    function scan(type, reset) {
        const section = document.querySelector(`[data-analytics="${type}"]`);
        const resultsEl = section.querySelector('[data-results]');
        const checkedEl = section.querySelector('[data-checked]');
        const missingEl = section.querySelector('[data-missing]');
        const cursorEl = section.querySelector('[data-cursor]');
        const limitEl = section.querySelector('[data-limit]');
        const deleteBase = section.getAttribute('data-delete-base');

        const limit = Math.min(Math.max(parseInt(limitEl.value || '30', 10), 1), 100);
        const currentCursor = reset ? 0 : parseInt(cursorEl.textContent || '0', 10);

        if (reset) {
            resultsEl.innerHTML = '';
            checkedEl.textContent = '0';
            missingEl.textContent = '0';
            cursorEl.textContent = '0';
        }

        updateStatus(section, 'Scan en cours...', false);

        fetch(`/analytics/check?type=${type}&cursor=${currentCursor}&limit=${limit}`, {
            headers: {
                'Accept': 'application/json',
            },
        })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    updateStatus(section, data.message, true);
                    if (!resultsEl.children.length) {
                        resultsEl.innerHTML = `<tr class="text-muted"><td colspan="5">${escapeHtml(data.message)}</td></tr>`;
                    }
                    return;
                }

                const totalChecked = parseInt(checkedEl.textContent || '0', 10) + (data.checked || 0);
                const totalMissing = parseInt(missingEl.textContent || '0', 10) + (data.missing || 0);

                checkedEl.textContent = totalChecked;
                missingEl.textContent = totalMissing;
                cursorEl.textContent = data.nextCursor || currentCursor;

                if (!data.items || data.items.length === 0) {
                    if (!resultsEl.querySelector('tr') || resultsEl.querySelector('[data-placeholder]')) {
                        resultsEl.innerHTML = '<tr class="text-muted" data-placeholder="true"><td colspan="5">Aucun fichier manquant dans ce lot.</td></tr>';
                    }
                } else {
                    const placeholder = resultsEl.querySelector('[data-placeholder]');
                    if (placeholder) {
                        placeholder.remove();
                    }
                    data.items.forEach(item => {
                        resultsEl.insertAdjacentHTML('beforeend', buildRow(item, deleteBase));
                    });
                }

                updateStatus(section, data.done ? 'Scan terminé pour ce lot.' : 'Lot terminé. Prêt pour le suivant.', false);
            })
            .catch(() => {
                updateStatus(section, 'Erreur réseau lors du scan.', true);
            });
    }

    function scanShortAudios(reset) {
        const section = document.querySelector('[data-short-audios="audios"]');
        const resultsEl = section.querySelector('[data-short-results]');
        const checkedEl = section.querySelector('[data-short-checked]');
        const shortEl = section.querySelector('[data-short-count]');
        const skippedEl = section.querySelector('[data-short-skipped]');
        const unknownEl = section.querySelector('[data-short-unknown]');
        const cursorEl = section.querySelector('[data-short-cursor]');
        const limitEl = section.querySelector('[data-short-limit]');
        const maxEl = section.querySelector('[data-short-max]');
        const deleteBase = section.getAttribute('data-delete-base');

        const limit = Math.min(Math.max(parseInt(limitEl.value || '20', 10), 1), 50);
        const maxDuration = Math.min(Math.max(parseInt(maxEl.value || '60', 10), 10), 600);
        const currentCursor = reset ? 0 : parseInt(cursorEl.textContent || '0', 10);

        if (reset) {
            resultsEl.innerHTML = '';
            checkedEl.textContent = '0';
            shortEl.textContent = '0';
            skippedEl.textContent = '0';
            unknownEl.textContent = '0';
            cursorEl.textContent = '0';
        }

        updateStatus(section, 'Scan en cours...', false);

        fetch(`/analytics/short-audios?cursor=${currentCursor}&limit=${limit}&maxDuration=${maxDuration}`, {
            headers: {
                'Accept': 'application/json',
            },
        })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    updateStatus(section, data.message, true);
                    if (!resultsEl.children.length) {
                        resultsEl.innerHTML = `<tr class="text-muted"><td colspan="5">${escapeHtml(data.message)}</td></tr>`;
                    }
                    return;
                }

                checkedEl.textContent = parseInt(checkedEl.textContent || '0', 10) + (data.checked || 0);
                shortEl.textContent = parseInt(shortEl.textContent || '0', 10) + (data.short || 0);
                skippedEl.textContent = parseInt(skippedEl.textContent || '0', 10) + (data.skippedLarge || 0);
                unknownEl.textContent = parseInt(unknownEl.textContent || '0', 10) + (data.unknown || 0);
                cursorEl.textContent = data.nextCursor || currentCursor;

                if (!data.items || data.items.length === 0) {
                    if (!resultsEl.querySelector('tr') || resultsEl.querySelector('[data-placeholder]')) {
                        resultsEl.innerHTML = '<tr class="text-muted" data-placeholder="true"><td colspan="5">Aucun audio court dans ce lot.</td></tr>';
                    }
                } else {
                    const placeholder = resultsEl.querySelector('[data-placeholder]');
                    if (placeholder) {
                        placeholder.remove();
                    }
                    data.items.forEach(item => {
                        resultsEl.insertAdjacentHTML('beforeend', buildShortRow(item, deleteBase));
                    });
                }

                updateStatus(section, data.done ? 'Scan terminé pour ce lot.' : 'Lot terminé. Prêt pour le suivant.', false);
            })
            .catch(() => {
                updateStatus(section, 'Erreur réseau lors du scan.', true);
            });
    }

    function runDuplicates(type) {
        const section = document.querySelector(`[data-duplicates="${type}"]`);
        const resultsEl = section.querySelector('[data-duplicate-results]');
        const totalEl = section.querySelector('[data-duplicate-total]');
        const modeEl = section.querySelector('[data-duplicate-mode]');
        const limitEl = section.querySelector('[data-duplicate-limit]');
        const deleteBase = section.getAttribute('data-delete-base');

        const mode = modeEl.value || 'exact';
        const limit = Math.min(Math.max(parseInt(limitEl.value || '50', 10), 1), 200);

        updateStatus(section, 'Analyse en cours...', false);

        fetch(`/analytics/duplicates?type=${type}&mode=${mode}&limit=${limit}`, {
            headers: {
                'Accept': 'application/json',
            },
        })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    updateStatus(section, data.message, true);
                    if (!resultsEl.children.length) {
                        resultsEl.innerHTML = `<tr class="text-muted"><td colspan="3">${escapeHtml(data.message)}</td></tr>`;
                    }
                    return;
                }

                totalEl.textContent = data.total || 0;

                if (!data.groups || data.groups.length === 0) {
                    resultsEl.innerHTML = '<tr class="text-muted" data-placeholder="true"><td colspan="3">Aucun doublon détecté.</td></tr>';
                } else {
                    resultsEl.innerHTML = '';
                    data.groups.forEach(group => {
                        resultsEl.insertAdjacentHTML('beforeend', buildDuplicateRow(group, deleteBase));
                    });
                }

                updateStatus(section, 'Analyse terminée.', false);
            })
            .catch(() => {
                updateStatus(section, 'Erreur réseau lors de l\'analyse.', true);
            });
    }

    document.querySelectorAll('[data-analytics]')?.forEach(section => {
        const type = section.getAttribute('data-analytics');
        section.querySelector('[data-scan-reset]').addEventListener('click', () => scan(type, true));
        section.querySelector('[data-scan-next]').addEventListener('click', () => scan(type, false));
    });

    const shortSection = document.querySelector('[data-short-audios="audios"]');
    if (shortSection) {
        shortSection.querySelector('[data-short-reset]').addEventListener('click', () => scanShortAudios(true));
        shortSection.querySelector('[data-short-next]').addEventListener('click', () => scanShortAudios(false));
    }

    document.querySelectorAll('[data-duplicates]')?.forEach(section => {
        const type = section.getAttribute('data-duplicates');
        section.querySelector('[data-duplicate-run]').addEventListener('click', () => runDuplicates(type));
    });
</script>
@endsection

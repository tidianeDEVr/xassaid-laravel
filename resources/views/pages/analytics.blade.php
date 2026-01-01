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
    </div>
</div>
@endsection

@section('scripts')
<script>
    const csrfToken = "{{ csrf_token() }}";

    function buildRow(item, deleteBase) {
                const url = item.url ? `<a href="${item.url}" target="_blank">${item.url}</a>` : "-";
        const status = item.status !== null && item.status !== undefined ? item.status : "-";
        return `
            <tr>
                <td>${item.id}</td>
                <td>${item.title || ''}</td>
                <td>${url}</td>
                <td>${status}${item.reason ? ' - ' + item.reason : ''}</td>
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

    function updateStatus(section, message, isError) {
        const statusEl = section.querySelector('[data-status]');
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
                        resultsEl.innerHTML = `<tr class="text-muted"><td colspan="5">${data.message}</td></tr>`;
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

    document.querySelectorAll('[data-analytics]')?.forEach(section => {
        const type = section.getAttribute('data-analytics');
        section.querySelector('[data-scan-reset]').addEventListener('click', () => scan(type, true));
        section.querySelector('[data-scan-next]').addEventListener('click', () => scan(type, false));
    });
</script>
@endsection

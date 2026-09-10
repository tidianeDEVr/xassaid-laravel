@extends('base')

@section('title', 'Publication YouTube')

@section('content')
<div class="page page-wide">
    <div class="page-head">
        <div>
            <h1>Publication YouTube <span class="n" id="mode"></span></h1>
            <div class="sub">Génération et publication automatiques des audios · <span id="clock"></span></div>
        </div>
        <div class="actions">
            <button class="btn" id="syncBtn" title="Resynchroniser le catalogue"><i class="ri-refresh-line"></i> Synchroniser</button>
            <button class="btn btn-primary" id="pauseBtn">…</button>
        </div>
    </div>

    @if (!$configured)
        <div class="flash warn"><i class="ri-error-warning-line"></i><div>Service non configuré. Renseigner <code>YOUTUBE_AUTOMATION_URL</code> et <code>YOUTUBE_AUTOMATION_TOKEN</code> dans le <code>.env</code> (le jeton doit correspondre à <code>API_TOKEN</code> du service xassaid-automation).</div></div>
    @endif
    <div class="flash err hidden" id="serviceError"><i class="ri-error-warning-line"></i><div class="txt"></div></div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row" style="justify-content:space-between;align-items:baseline">
                <div><span style="font-size:28px;font-weight:600;letter-spacing:-.02em" id="pct">0 %</span> <span class="muted" id="pctLbl"></span></div>
                <div class="muted small" id="eta"></div>
            </div>
            <div class="progress mt-1"><span id="pctBar" style="width:0"></span></div>
        </div>
    </div>

    <div class="grid grid-stats mb-3" id="stats"></div>

    <div class="grid grid-2 mb-3">
        <div class="card" style="margin:0"><div class="card-head">En cours</div><div class="card-body" id="now"></div></div>
        <div class="card" style="margin:0"><div class="card-head">Prochaine publication</div><div class="card-body" id="sched"></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-head">Par catégorie</div>
        <div class="table-wrap">
            <table class="table" id="cats">
                <thead><tr><th>Type</th><th>Catégorie</th><th>Avancement</th><th class="right">Publiés</th><th class="right">Échecs</th><th class="right">Total</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-head">Audios</div>
        <div class="toolbar">
            <select class="select sm w-auto" id="fStatus" style="flex:0 0 auto;min-width:0">
                <option value="">Tous les statuts</option>
                <option value="pending">À faire</option><option value="rendering">Rendu en cours</option>
                <option value="rendered">Vidéo prête</option><option value="uploading">Upload en cours</option>
                <option value="uploaded">Publié</option><option value="failed">Échec (à réessayer)</option>
                <option value="skipped">Ignoré</option>
            </select>
            <select class="select sm" id="fCat" data-search data-allow-empty data-placeholder="Toutes les catégories"><option value="">Toutes les catégories</option></select>
            <div class="search"><i class="ri-search-line"></i><input class="input sm" id="fQ" placeholder="Rechercher un titre…"></div>
            <span class="result" id="count"></span>
            <div class="btn-group"><button class="btn btn-sm btn-icon" id="prev" aria-label="Page précédente"><i class="ri-arrow-left-s-line"></i></button><button class="btn btn-sm btn-icon" id="next" aria-label="Page suivante"><i class="ri-arrow-right-s-line"></i></button></div>
        </div>
        <div class="table-wrap">
            <table class="table" id="audios">
                <thead><tr><th class="idx">#</th><th>Titre</th><th>Catégorie</th><th>Statut</th><th>Durée</th><th>YouTube</th><th>Mis à jour</th><th></th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head">Journal</div>
        <div class="log" id="log"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const API = '/youtube/api';
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const $ = (s) => document.querySelector(s);
const LABELS = {pending:'À faire',rendering:'Rendu',rendered:'Prête',uploading:'Upload',uploaded:'Publié',failed:'Échec',skipped:'Ignoré'};
const BADGE = {pending:'',rendering:'info',rendered:'warn',uploading:'info',uploaded:'ok',failed:'warn',skipped:'err'};
const STEPS = {'téléchargement':'Téléchargement de l’audio', capture:'Capture du lecteur (Chromium)', encodage:'Encodage vidéo (ffmpeg)', assemblage:'Assemblage intro/outro', upload:'Envoi vers YouTube', miniature:'Miniature', playlist:'Playlist', synchronisation:'Synchronisation du catalogue'};
let page = 0, PAGE = 50;
const esc = window.escapeHtml;
const fmtT = (s) => { s = Math.round(s || 0); const h = Math.floor(s/3600), m = Math.floor(s%3600/60), r = s%60; return (h ? h+':' : '') + String(m).padStart(2,'0') + ':' + String(r).padStart(2,'0'); };
const fmtD = (iso) => iso ? new Date(iso).toLocaleString('fr-FR', {dateStyle:'short', timeStyle:'short'}) : '—';
const rel = (iso) => { if (!iso) return ''; const d = (new Date(iso) - Date.now())/60000; if (Math.abs(d) < 1) return 'maintenant'; if (Math.abs(d) < 90) return `dans ${Math.round(d)} min`; return `dans ${(d/60).toFixed(1)} h`; };
const pill = (st) => `<span class="badge ${BADGE[st] || ''}">${LABELS[st] || st}</span>`;

async function api(path, opts = {}) {
    const r = await fetch(API + path, {headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'}, ...opts});
    const data = await r.json().catch(() => ({}));
    if (!r.ok) throw new Error(data.error || data.detail || ('Erreur ' + r.status));
    return data;
}
async function post(path) { try { await api(path, {method:'POST'}); } catch (e) { alert(e.message); } refresh(); }
function showError(msg) { const el = $('#serviceError'); if (msg) { el.querySelector('.txt').textContent = msg; el.classList.remove('hidden'); } else el.classList.add('hidden'); }

async function refreshStatus() {
    const s = await api('/status');
    showError(null);
    $('#clock').textContent = new Date(s.now).toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'}) + ' (' + s.timezone + ')';
    $('#mode').textContent = s.dry_run ? 'dry run, aucun upload' : '';
    const st = s.stats;
    $('#pct').textContent = s.percent + ' %';
    $('#pctBar').style.width = s.percent + '%';
    $('#pctLbl').textContent = `${st.uploaded} / ${st.total} audios publiés`;
    $('#eta').textContent = st.total ? `≈ ${s.estimate_days_left} jours restants à ${s.schedule.max_uploads_per_day} publications/jour` : 'Catalogue en cours de synchronisation…';
    $('#stats').innerHTML = [
        ['Publiés', st.uploaded, 'uploaded'], ['À faire', st.pending, 'pending'],
        ['Vidéos prêtes', st.rendered, 'rendered'], ['En cours', st.rendering + st.uploading, 'rendering'],
        ['Échecs', st.failed, 'failed'], ['Ignorés', st.skipped, 'skipped'],
    ].map(([k, v, c]) => `<div class="card stat"><div class="value">${v}</div><div class="label">${k}</div><div class="mt-1">${pill(c)}</div></div>`).join('');

    const w = s.worker, c = w.current;
    if (c) {
        const p = Math.round(c.progress * 100);
        $('#now').innerHTML = `<div style="font-size:16px;font-weight:600"><span class="pulse"></span>${esc(c.title)} <span class="muted" style="font-weight:400">— ${esc(c.category_title || '')}</span></div>
            <div>${STEPS[c.step] || esc(c.step)} <span class="muted">· ${p} %</span></div>
            <div class="progress mt-1 mb-2"><span style="width:${p}%"></span></div>
            <div class="muted small">Depuis ${fmtD(c.started_at)}</div>`;
    } else {
        $('#now').innerHTML = `<div style="font-size:16px;font-weight:600" class="${s.paused ? '' : 'muted'}">${s.paused ? 'En pause' : 'Inactif'}</div>
            <div class="muted">${esc(w.sleep_reason || '')} ${w.sleeping_until ? '· reprise ' + rel(w.sleeping_until) : ''}</div>
            ${w.last_error ? `<div class="err-text">Dernière erreur : ${esc(w.last_error)}</div>` : ''}`;
    }
    const sc = s.schedule, q = sc.quota;
    $('#sched').innerHTML = `
        <div style="font-size:16px;font-weight:600">${sc.can_upload ? 'Créneau ouvert' : fmtD(sc.next_at) + ' <span class="muted" style="font-weight:400">(' + rel(sc.next_at) + ')</span>'}</div>
        <div class="muted mb-2">${esc(sc.reason || 'Dès qu’une vidéo est prête')}</div>
        <table class="kv"><tr><td>Aujourd’hui (quota Pacifique)</td><td>${q.uploads} / ${sc.max_uploads_per_day} vidéos</td></tr>
        <tr><td>Quota API estimé</td><td>${q.units} / ${sc.quota_limit} unités</td></tr>
        <tr><td>Plage horaire</td><td>${sc.window}</td></tr>
        <tr><td>Dernière publication</td><td>${fmtD(s.last_upload_at)}</td></tr>
        <tr><td>Jeton YouTube</td><td>${s.token_error ? '<span style="color:var(--danger)">expiré / révoqué</span>' : s.token_present ? '<span style="color:var(--accent-ink)">valide</span>' : '<span style="color:var(--danger)">absent</span>'}</td></tr></table>
        ${s.token_error ? `<div class="err mt-1">${esc(s.token_error)}</div>` : ''}`;
    $('#pauseBtn').innerHTML = s.paused ? '<i class="ri-play-line"></i> Reprendre' : '<i class="ri-pause-line"></i> Mettre en pause';
    $('#pauseBtn').onclick = () => post(s.paused ? '/resume' : '/pause');
}

async function refreshCats() {
    const cats = await api('/categories');
    $('#cats tbody').innerHTML = cats.map((c) => {
        const p = c.total ? Math.round(100 * c.uploaded / c.total) : 0;
        return `<tr><td class="muted">${esc(c.type)}</td><td><a href="#" data-cat="${esc(c.slug)}">${esc(c.title)}</a></td>
            <td><span class="progress thin mini"><span style="width:${p}%"></span></span>${p} %${c.in_progress ? ' <span class="badge info">en cours</span>' : ''}</td>
            <td class="right">${c.uploaded}</td><td class="right">${c.failed || 0}</td><td class="right">${c.total}</td></tr>`;
    }).join('');
    const sel = $('#fCat'), cur = sel.value;
    sel.innerHTML = '<option value="">Toutes les catégories</option>' + cats.map((c) => `<option value="${esc(c.slug)}" data-group="${esc(c.type)}">${esc(c.title)} (${c.uploaded}/${c.total})</option>`).join('');
    sel.value = cur;
    sel.dispatchEvent(new Event('combobox:sync'));
    document.querySelectorAll('[data-cat]').forEach((a) => a.onclick = (e) => { e.preventDefault(); sel.value = a.dataset.cat; sel.dispatchEvent(new Event('combobox:sync')); page = 0; refreshAudios(); $('#audios').scrollIntoView({behavior: 'smooth'}); });
}

async function refreshAudios() {
    const qs = new URLSearchParams({status: $('#fStatus').value, category: $('#fCat').value, q: $('#fQ').value, limit: PAGE, offset: page * PAGE});
    const d = await api('/audios?' + qs);
    $('#count').textContent = `${d.total} audio(s) · page ${page + 1}/${Math.max(1, Math.ceil(d.total / PAGE))}`;
    $('#audios tbody').innerHTML = d.items.map((a, i) => `<tr>
        <td class="idx">${page * PAGE + i + 1}</td>
        <td><span class="title">${esc(a.title)}</span>${a.error ? `<div class="err-text">${esc(a.error).slice(0, 300)}</div>` : ''}</td>
        <td>${esc(a.category_title)} <span class="type-tag">· ${esc(a.category_type)}</span></td>
        <td>${pill(a.status)}${a.attempts > 1 ? ` <span class="muted">×${a.attempts}</span>` : ''}</td>
        <td>${a.duration_sec ? fmtT(a.duration_sec) : ''}</td>
        <td>${a.youtube_id && a.youtube_id !== 'dry-run' ? `<a target="_blank" href="https://youtu.be/${esc(a.youtube_id)}">youtu.be/${esc(a.youtube_id)}</a>` : (a.youtube_id || '')}</td>
        <td class="muted small nowrap">${fmtD(a.uploaded_at || a.updated_at)}</td>
        <td class="actions-cell"><div class="btn-group">
            ${['failed','skipped','uploaded','rendered'].includes(a.status) ? `<button class="btn btn-sm" onclick="act(${a.id},'retry')">Relancer</button>` : ''}
            ${['pending','failed','rendered'].includes(a.status) ? `<button class="btn btn-sm" onclick="act(${a.id},'skip')">Ignorer</button>` : ''}
            ${['pending','failed'].includes(a.status) ? `<button class="btn btn-sm btn-icon" onclick="act(${a.id},'priority')" title="Traiter en premier"><i class="ri-arrow-up-line"></i></button>` : ''}
        </div></td></tr>`).join('') || '<tr class="empty-row"><td colspan="8">Aucun audio pour ces filtres.</td></tr>';
}
window.act = async (id, action) => {
    if (action === 'retry' && !confirm('Relancer cet audio (nouveau rendu + nouvel upload) ?')) return;
    await post(`/audios/${id}/${action}`);
};

async function refreshLog() {
    const ev = await api('/events');
    $('#log').innerHTML = ev.map((e) => `<div class="${e.level}"><time>${fmtD(e.ts)}</time>${esc(e.message).replace(/(https?:\/\/\S+)/g, '<a target="_blank" href="$1">$1</a>')}</div>`).join('');
}

async function refresh() {
    try { await Promise.all([refreshStatus(), refreshCats(), refreshAudios(), refreshLog()]); }
    catch (e) { showError(e.message); }
}
['#fStatus', '#fCat'].forEach((s) => $(s).onchange = () => { page = 0; refreshAudios(); });
let qt; $('#fQ').oninput = () => { clearTimeout(qt); qt = setTimeout(() => { page = 0; refreshAudios(); }, 300); };
$('#prev').onclick = () => { if (page > 0) { page--; refreshAudios(); } };
$('#next').onclick = () => { page++; refreshAudios(); };
$('#syncBtn').onclick = () => post('/sync');
@if ($configured)
refresh();
setInterval(() => { refreshStatus().catch((e) => showError(e.message)); refreshLog().catch(() => {}); }, 5000);
setInterval(() => { refreshCats().catch(() => {}); refreshAudios().catch(() => {}); }, 30000);
@endif
</script>
@endsection

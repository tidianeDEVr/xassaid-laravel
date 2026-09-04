@extends('base')

@section('title', 'Vidéos')

@section('content')
@php
    $tabs = [
        'pending_review' => 'En attente',
        'published' => 'Publiées',
        'rejected' => 'Rejetées',
        'failed' => 'Échecs',
        'processing' => 'En traitement',
    ];
    $withReason = in_array($status, ['rejected', 'failed'], true);
@endphp
<div class="page page-wide">
    <div class="page-head">
        <div>
            <h1>Vidéos</h1>
            <div class="sub">Modération du feed vidéo de l'application.</div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" data-open="#importModal"><i class="ri-download-cloud-2-line"></i> Importer des vidéos</button>
        </div>
    </div>

    @include('partials.flash')

    <div class="tabs">
        @foreach ($tabs as $key => $label)
            <a class="{{ $status === $key ? 'active' : '' }}" href="{{ url('/videos?status=' . $key) }}">{{ $label }} <span class="n">{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </div>

    <div class="card">
        <form class="toolbar" method="get" action="/videos">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="search grow" style="max-width:360px"><i class="ri-search-line"></i><input class="input" type="search" name="q" value="{{ $q }}" placeholder="Description, khassida ou auteur…"></div>
            <button class="btn" type="submit">Rechercher</button>
            @if ($q !== '')
                <a class="btn btn-ghost" href="/videos?status={{ $status }}">Réinitialiser</a>
            @endif
        </form>
        <div class="table-wrap">
            <table class="table stack">
                <thead>
                    <tr>
                        <th class="idx">#</th><th>Aperçu</th><th>Auteur</th><th>Description</th><th>Durée</th><th>Reçue le</th>
                        @if ($withReason)<th>Motif</th>@endif
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($videos as $video)
                        <tr>
                            <td class="idx">{{ $videos->firstItem() + $loop->index }}</td>
                            <td data-label="Aperçu">
                                @if ($video->poster_path)
                                    <img class="thumb tall" src="{{ $video->posterUrl() }}" alt="" />
                                @else
                                    <span class="thumb-empty"><i class="ri-video-line"></i></span>
                                @endif
                            </td>
                            <td data-label="Auteur">
                                <div class="title">{{ $video->author?->display_name ?? '?' }}
                                    @if ($video->author?->is_certified)<i class="ri-verified-badge-fill" style="color:var(--accent)" title="Certifié"></i>@endif
                                </div>
                                <div class="slug">{{ '@' . ($video->author?->username ?? '?') }}</div>
                            </td>
                            <td data-label="Description" style="max-width: 360px;">
                                {{ \Illuminate\Support\Str::limit($video->description, 120) }}
                                @if ($video->khassida_title)<div class="small muted">Khassida : {{ $video->khassida_title }}</div>@endif
                            </td>
                            <td data-label="Durée" class="nowrap">
                                {{ $video->duration_ms ? gmdate($video->duration_ms >= 3600000 ? 'G:i:s' : 'i:s', (int) round($video->duration_ms / 1000)) : '—' }}
                            </td>
                            <td data-label="Reçue le" class="muted small nowrap">{{ $video->created_at->format('d/m/Y H:i') }}</td>
                            @if ($withReason)<td data-label="Motif" class="small">{{ $video->rejected_reason ?? '—' }}</td>@endif
                            <td class="actions-cell">
                                <div class="btn-group">
                                    @if ($video->download_path || $video->hls_path)
                                        <button class="btn btn-sm btn-icon" title="Lire" data-open="#videoPreviewModal"
                                            data-src="{{ $video->downloadUrl() ?? $video->hlsUrl() }}" data-kind="{{ $video->download_path ? 'mp4' : 'hls' }}"
                                            data-modal-title="{{ $video->author?->display_name }} — {{ \Illuminate\Support\Str::limit($video->description, 60) }}"><i class="ri-play-circle-line"></i></button>
                                    @endif
                                    @if ($video->status === 'failed' && $video->source_url)
                                        <form class="inline" method="post" action="{{ url('/videos/' . $video->id . '/retry') }}" onsubmit="return confirm('Relancer l\'import de cette vidéo ?')">
                                            @csrf
                                            <button class="btn btn-sm" type="submit"><i class="ri-restart-line"></i> Relancer</button>
                                        </form>
                                    @endif
                                    @if ($video->status === 'pending_review')
                                        <form class="inline" method="post" action="{{ url('/videos/' . $video->id . '/approve') }}" onsubmit="return confirm('Publier cette vidéo ?')">
                                            @csrf
                                            <button class="btn btn-sm btn-accent" type="submit"><i class="ri-check-line"></i> Publier</button>
                                        </form>
                                        <button class="btn btn-sm btn-danger" data-open="#videoRejectModal" data-action="/videos/{{ $video->id }}/reject"><i class="ri-close-line"></i> Rejeter</button>
                                    @endif
                                    <form class="inline" method="post" action="{{ url('/videos/' . $video->id) }}" onsubmit="return confirm('Supprimer définitivement cette vidéo et ses fichiers ?')">
                                        @csrf @method('delete')
                                        <button class="btn btn-sm btn-danger btn-icon" type="submit" title="Supprimer"><i class="ri-delete-bin-6-line"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="{{ $withReason ? 8 : 7 }}">Aucune vidéo dans cet onglet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $videos->links('partials.pagination') }}
    </div>
</div>

<x-modal id="videoPreviewModal" title="Aperçu">
    <div class="modal-body dark" style="margin:-18px"><video id="videoPreviewPlayer" controls playsinline></video></div>
</x-modal>

<x-modal id="videoRejectModal" title="Rejeter la vidéo" size="sm" form="">
    <div class="field">
        <label class="req" for="rejectReason">Motif du rejet</label>
        <input type="text" required maxlength="255" name="reason" class="input" id="rejectReason" placeholder="Contenu inapproprié, qualité insuffisante…" />
        <div class="help">Le motif est visible par l'auteur dans l'application.</div>
    </div>
    <x-slot:footer>
        <button type="button" class="btn" data-close>Annuler</button>
        <button type="submit" class="btn btn-danger solid">Rejeter</button>
    </x-slot:footer>
</x-modal>

<x-modal id="importModal" title="Importer des vidéos" size="lg" form="" action="{{ url('/videos/import') }}">
    <div class="field">
        <label class="req" for="importAccount">Publier sur le compte</label>
        <select name="app_user_id" id="importAccount" class="select" required data-search data-placeholder="Choisir un compte">
            <option value="">Choisir un compte</option>
            @foreach ($appUsers as $appUser)
                <option value="{{ $appUser->id }}" data-group="{{ '@' . $appUser->username }}">{{ $appUser->display_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label class="req" for="importLinks">Liens (un par ligne, 20 max)</label>
        <textarea name="links" id="importLinks" class="textarea" rows="6" required placeholder="https://www.youtube.com/shorts/…&#10;https://www.instagram.com/reel/…"></textarea>
        <div class="help">Chaque lien est téléchargé, transcodé en trois qualités puis publié directement. La description reprend le titre de la vidéo. L'avancement est visible dans l'onglet « En traitement ».</div>
    </div>
    <x-slot:footer>
        <button type="button" class="btn" data-close>Annuler</button>
        <button type="submit" class="btn btn-primary">Lancer l'import</button>
    </x-slot:footer>
</x-modal>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.15/dist/hls.min.js"></script>
<script>
    // Aperçu : MP4 en lecture native (cas normal) ; HLS en repli —
    // natif sur Safari, hls.js ailleurs.
    const previewModal = document.getElementById('videoPreviewModal');
    const previewPlayer = document.getElementById('videoPreviewPlayer');
    let hlsInstance = null;
    previewModal.addEventListener('modal:open', (event) => {
        const t = event.detail.trigger;
        const src = t.dataset.src, kind = t.dataset.kind || 'hls';
        if (kind === 'mp4' || previewPlayer.canPlayType('application/vnd.apple.mpegurl')) {
            previewPlayer.src = src;
        } else if (window.Hls && Hls.isSupported()) {
            hlsInstance = new Hls();
            hlsInstance.loadSource(src);
            hlsInstance.attachMedia(previewPlayer);
        }
    });
    previewModal.addEventListener('modal:close', () => {
        previewPlayer.pause();
        previewPlayer.removeAttribute('src');
        previewPlayer.load();
        if (hlsInstance) { hlsInstance.destroy(); hlsInstance = null; }
    });
</script>
@endsection

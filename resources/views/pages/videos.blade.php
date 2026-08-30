@extends('base')

@section('content')
@php
    $canManageAdmins = auth()->check() && auth()->user()->email === 'cheikhtiindiaye@gmail.com';
    $tabs = [
        'pending_review' => 'En attente',
        'published' => 'Publiées',
        'rejected' => 'Rejetées',
        'failed' => 'Échecs',
        'processing' => 'En traitement',
    ];
@endphp
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-3 gap-2">
      <h1>Vidéos</h1>
      <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#importModal">
        <i class="ri-download-cloud-2-line"></i> Importer des vidéos
      </button>
    </div>
    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <ul class="nav nav-tabs mb-3">
      @foreach ($tabs as $key => $label)
        <li class="nav-item">
          <a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ url('/videos?status=' . $key) }}">
            {{ $label }}
            <span class="badge {{ $status === $key ? 'bg-dark' : 'bg-secondary' }}">{{ $counts[$key] ?? 0 }}</span>
          </a>
        </li>
      @endforeach
    </ul>

    <div class="table-responsive">
      <table id="videosTable" class="table table-bordered align-middle" style="width: 100%">
        <thead>
          <tr>
            <th>N*</th>
            <th>Aperçu</th>
            <th>Auteur</th>
            <th>Description</th>
            <th>Durée</th>
            <th>Reçue le</th>
            @if ($status === 'rejected' || $status === 'failed')
              <th>Motif</th>
            @endif
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($videos as $index => $video)
          <tr>
            <td>{{ $index + 1 }}</td>
            <td>
              @if ($video->poster_path)
                <img src="{{ $video->posterUrl() }}" alt="poster"
                     style="width: 54px; height: 96px; object-fit: cover; border-radius: 6px;" />
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
            <td>
              {{ $video->author?->display_name ?? '?' }}<br />
              <small class="text-muted">
                {{ '@' . ($video->author?->username ?? '?') }}
                @if ($video->author?->is_certified)
                  <i class="ri-verified-badge-fill text-success" title="Certifié"></i>
                @endif
              </small>
            </td>
            <td style="max-width: 320px;">
              {{ \Illuminate\Support\Str::limit($video->description, 120) }}
              @if ($video->khassida_title)
                <br /><small class="text-muted">Khassida : {{ $video->khassida_title }}</small>
              @endif
            </td>
            <td>
              @if ($video->duration_ms)
                {{ gmdate($video->duration_ms >= 3600000 ? 'G:i:s' : 'i:s', (int) round($video->duration_ms / 1000)) }}
              @else
                —
              @endif
            </td>
            <td>{{ $video->created_at->format('d/m/Y H:i') }}</td>
            @if ($status === 'rejected' || $status === 'failed')
              <td>{{ $video->rejected_reason ?? '—' }}</td>
            @endif
            <td>
              <div class="d-flex gap-2">
                @if ($video->download_path || $video->hls_path)
                  {{-- Aperçu : MP4 en priorité (lecture native, pas de CORS),
                       HLS en repli pour les vidéos sans download.mp4. --}}
                  <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                    data-bs-target="#videoPreviewModal"
                    data-src="{{ $video->downloadUrl() ?? $video->hlsUrl() }}"
                    data-kind="{{ $video->download_path ? 'mp4' : 'hls' }}"
                    data-title="{{ $video->author?->display_name }} — {{ \Illuminate\Support\Str::limit($video->description, 60) }}">
                    <i class="ri-play-circle-line"></i>
                  </button>
                @endif
                @if ($video->status === 'pending_review')
                  <form method="post" action="{{ url('/videos/' . $video->id . '/approve') }}">
                    @csrf
                    <button class="btn btn-sm btn-success" type="submit"
                      onclick="return confirm('Publier cette vidéo ?')">
                      <i class="ri-check-line"></i> Publier
                    </button>
                  </form>
                  <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                    data-bs-target="#videoRejectModal" data-id="{{ $video->id }}">
                    <i class="ri-close-line"></i> Rejeter
                  </button>
                @endif
                @if ($canManageAdmins)
                  <form method="post" action="{{ url('/videos/' . $video->id) }}"
                    onsubmit="return confirm('Supprimer définitivement cette vidéo et ses fichiers ?')">
                    @csrf
                    @method('delete')
                    <button class="btn btn-sm btn-outline-danger" type="submit">
                      <i class="ri-delete-bin-6-line"></i>
                    </button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
</div>

<div class="modal fade" id="videoPreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-6" id="videoPreviewTitle">Aperçu</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center bg-black">
        <video id="videoPreviewPlayer" controls playsinline
               style="max-width: 100%; max-height: 70vh;"></video>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="videoRejectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" id="videoRejectForm">
        @csrf
        <div class="modal-header">
          <h1 class="modal-title fs-6">Rejeter la vidéo</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <label for="rejectReason" class="form-label"><span class="text-danger">*</span>Motif du rejet</label>
          <input type="text" required maxlength="255" name="reason" class="form-control" id="rejectReason"
                 placeholder="Contenu inapproprié, qualité insuffisante..." />
          <small class="text-muted">Le motif sera visible par l'auteur dans l'application.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-danger">Rejeter</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.15/dist/hls.min.js"></script>
<script defer>
    $(document).ready(function () {
      $("#videosTable").DataTable({ order: [] });
    });

    // Aperçu : MP4 en lecture native (cas normal) ; HLS en repli —
    // natif sur Safari, hls.js ailleurs.
    const previewModal = document.getElementById('videoPreviewModal');
    const previewPlayer = document.getElementById('videoPreviewPlayer');
    let hlsInstance = null;

    if (previewModal) {
      previewModal.addEventListener('show.bs.modal', function (event) {
        const src = event.relatedTarget.getAttribute('data-src');
        const kind = event.relatedTarget.getAttribute('data-kind') || 'hls';
        document.getElementById('videoPreviewTitle').textContent =
          event.relatedTarget.getAttribute('data-title') || 'Aperçu';

        if (kind === 'mp4') {
          previewPlayer.src = src;
        } else if (previewPlayer.canPlayType('application/vnd.apple.mpegurl')) {
          previewPlayer.src = src;
        } else if (window.Hls && Hls.isSupported()) {
          hlsInstance = new Hls();
          hlsInstance.loadSource(src);
          hlsInstance.attachMedia(previewPlayer);
        }
      });
      previewModal.addEventListener('hidden.bs.modal', function () {
        previewPlayer.pause();
        previewPlayer.removeAttribute('src');
        previewPlayer.load();
        if (hlsInstance) { hlsInstance.destroy(); hlsInstance = null; }
      });
    }

    const rejectModal = document.getElementById('videoRejectModal');
    if (rejectModal) {
      rejectModal.addEventListener('show.bs.modal', function (event) {
        const id = event.relatedTarget.getAttribute('data-id');
        document.getElementById('videoRejectForm').action = `/videos/${id}/reject`;
      });
    }
</script>

<!-- Import de vidéos par liens (YouTube Shorts, Instagram Reels...) -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" action="{{ url('/videos/import') }}">
        @csrf
        <div class="modal-header">
          <h1 class="modal-title fs-5">Importer des vidéos</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><span class="text-danger">*</span>Publier sur le compte</label>
            <select name="app_user_id" class="form-select" required>
              <option value="" disabled selected>Choisir un compte…</option>
              @foreach ($appUsers as $appUser)
                <option value="{{ $appUser->id }}">
                  {{ '@' . $appUser->username }} — {{ $appUser->display_name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="mb-1">
            <label class="form-label"><span class="text-danger">*</span>Liens (un par ligne, 20 max)</label>
            <textarea name="links" class="form-control" rows="6" required
              placeholder="https://www.youtube.com/shorts/…&#10;https://www.instagram.com/reel/…"></textarea>
          </div>
          <small class="text-muted">
            Chaque lien est téléchargé (yt-dlp), transcodé en 3 qualités puis
            publié directement. La description reprend le titre de la vidéo.
            Suivez l'avancement dans l'onglet « En traitement ».
          </small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-dark">Lancer l'import</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

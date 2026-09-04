@extends('base')

@section('title', 'Tableau de bord')

@section('content')
@php
    $isSuperAdmin = auth()->user()->isSuperAdmin();
    $typeLabels = ['makk-gni' => 'Makk gni', 'kourels-yii' => 'Kourels yii', 'rajass-kat-yii' => 'Rajass kat yii', 'autres' => 'Autres'];
    $stats = [
        ['ri-folder-music-line', $overview['audiosCounts'], 'audios', '/audios'],
        ['ri-price-tag-3-line', $overview['categoriesCounts'], 'catégories', '/categories/audios'],
        ['ri-news-line', $overview['articlesCounts'], 'articles', '/articles'],
        ['ri-file-pdf-2-line', $overview['filesCounts'], 'fichiers PDF', '/library'],
    ];
    if ($isSuperAdmin) {
        $stats[] = ['ri-video-line', $overview['videosPending'], 'vidéos à modérer', '/videos'];
        $stats[] = ['ri-user-3-line', $overview['appUsersCounts'], 'comptes app', '/app-users'];
    }
    $showYoutube = $isSuperAdmin && $youtubeConfigured;
@endphp
<div class="page">
    <div class="page-head">
        <div>
            <h1>Tableau de bord</h1>
            <div class="sub">{{ $overview['audiosThisMonth'] }} audio(s) ajouté(s) ce mois-ci{{ $isSuperAdmin ? ', ' . $overview['videosPublished'] . ' vidéo(s) publiée(s) dans le feed' : '' }}.</div>
        </div>
        <div class="actions">
            <a href="/audios" class="btn btn-primary"><i class="ri-add-line"></i> Ajouter un audio</a>
            <a href="/articles/create" class="btn"><i class="ri-quill-pen-line"></i> Nouvel article</a>
        </div>
    </div>

    @include('partials.flash')

    <div class="grid grid-stats mb-3">
        @foreach ($stats as [$icon, $value, $label, $href])
            <a href="{{ $href }}" class="card stat">
                <i class="{{ $icon }}"></i>
                <div class="value">{{ number_format($value, 0, ',', ' ') }}</div>
                <div class="label">{{ $label }}</div>
            </a>
        @endforeach
    </div>

    <div class="grid grid-2">
        @if ($showYoutube)
            <div class="card" style="margin:0">
                <div class="card-head"><span><i class="ri-youtube-line"></i> Publication YouTube</span><a href="/youtube" class="btn btn-sm">Suivi détaillé</a></div>
                <div class="card-body">
                    @if (isset($youtube['error']))
                        <p class="flash err" style="margin:0"><i class="ri-error-warning-line"></i> {{ $youtube['error'] }}</p>
                    @else
                        @php $s = $youtube['stats']; $sched = $youtube['schedule']; $cur = $youtube['worker']['current'] ?? null; @endphp
                        <div class="row" style="justify-content:space-between;align-items:baseline">
                            <div><span style="font-size:28px;font-weight:600;letter-spacing:-.02em">{{ $youtube['percent'] }} %</span> <span class="muted">{{ $s['uploaded'] }} / {{ $s['total'] }} audios publiés</span></div>
                            <div class="muted small">≈ {{ $youtube['estimate_days_left'] }} jours restants</div>
                        </div>
                        <div class="progress mt-1 mb-2"><span style="width: {{ $youtube['percent'] }}%"></span></div>
                        <table class="kv">
                            <tr><td>Aujourd'hui</td><td>{{ $sched['quota']['uploads'] }} / {{ $sched['max_uploads_per_day'] }} vidéos</td></tr>
                            <tr><td>Quota API</td><td>{{ $sched['quota']['units'] }} / {{ $sched['quota_limit'] }}</td></tr>
                            <tr><td>Prêtes / échecs</td><td>{{ $s['rendered'] }} / {{ $s['failed'] + $s['skipped'] }}</td></tr>
                            <tr><td>État</td><td>{{ $youtube['paused'] ? 'En pause' : ($cur ? 'En cours : ' . $cur['title'] . ' (' . $cur['step'] . ' ' . round($cur['progress'] * 100) . ' %)' : 'Inactif') }}</td></tr>
                        </table>
                    @endif
                </div>
            </div>
        @endif

        <div class="card" style="margin:0">
            <div class="card-head">Audios par type</div>
            @foreach ($typeLabels as $type => $label)
                @php $n = $audiosByType[$type] ?? 0; $pct = $overview['audiosCounts'] ? round(100 * $n / $overview['audiosCounts']) : 0; @endphp
                <div class="bar-row">
                    <div class="row"><span>{{ $label }}</span><span>{{ number_format($n, 0, ',', ' ') }} <span class="muted small">· {{ $pct }} %</span></span></div>
                    <div class="progress thin"><span style="width: {{ $pct }}%; background: var(--ink-2)"></span></div>
                </div>
            @endforeach
        </div>

        <div class="card" style="margin:0">
            <div class="card-head">À corriger</div>
            <ul class="list">
                <li>
                    <div><b>{{ $categoriesWithoutCover->count() }}</b> catégorie(s) sans image
                        @if ($categoriesWithoutCover->isNotEmpty())<div class="muted small">{{ $categoriesWithoutCover->take(4)->pluck('title')->join(', ') }}{{ $categoriesWithoutCover->count() > 4 ? '…' : '' }}</div>@endif
                    </div>
                    <a href="/categories/audios" class="small">Voir</a>
                </li>
                <li>
                    <div><b>{{ $emptyCategories->count() }}</b> catégorie(s) sans audio
                        @if ($emptyCategories->isNotEmpty())<div class="muted small">{{ $emptyCategories->take(4)->pluck('title')->join(', ') }}{{ $emptyCategories->count() > 4 ? '…' : '' }}</div>@endif
                    </div>
                    <a href="/categories/audios" class="small">Voir</a>
                </li>
                @if ($isSuperAdmin)
                    <li><div><b>{{ $overview['videosPending'] }}</b> vidéo(s) en attente de modération</div><a href="/videos" class="small">Modérer</a></li>
                @endif
            </ul>
        </div>

        <div class="card" style="margin:0">
            <div class="card-head"><span>Derniers audios ajoutés</span><a href="/audios" class="small">Tout voir</a></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Titre</th><th>Catégorie</th><th class="right">Ajouté</th></tr></thead>
                    <tbody>
                        @forelse ($recentAudios as $audio)
                            <tr>
                                <td>{{ $audio->title }}</td>
                                <td class="muted">{{ $audio->category->title ?? '—' }}</td>
                                <td class="right muted small nowrap">{{ optional($audio->created_at)->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr class="empty-row"><td colspan="3">Aucun audio</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="margin:0">
            <div class="card-head"><span>Derniers articles</span><a href="/articles" class="small">Tout voir</a></div>
            <ul class="list">
                @forelse ($recentArticles as $article)
                    <li><a href="/articles/{{ $article->id }}/edit" style="color:inherit">{{ $article->title }}</a><span class="muted small nowrap">{{ optional($article->created_at)->diffForHumans() }}</span></li>
                @empty
                    <li class="muted">Aucun article</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection

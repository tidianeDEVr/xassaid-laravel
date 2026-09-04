@extends('base')

@section('title', 'Audios')

@section('content')
@php
    $isSuperAdmin = auth()->user()->isSuperAdmin();
    $typeLabels = ['makk-gni' => 'Makk gni', 'kourels-yii' => 'Kourels yii', 'rajass-kat-yii' => 'Rajass kat yii', 'autres' => 'Autres'];
@endphp
<div class="page">
    <div class="page-head">
        <div>
            <h1>Audios <span class="n">{{ number_format($audios->total(), 0, ',', ' ') }}</span></h1>
            <div class="sub">Fichiers MP3 du catalogue, classés par catégorie.</div>
        </div>
        <div class="actions">
            <a href="/categories/audios" class="btn"><i class="ri-price-tag-3-line"></i> Catégories</a>
            <button type="button" class="btn" data-open="#audioImportModal"><i class="ri-download-cloud-2-line"></i> Importer des liens</button>
            <button type="button" class="btn btn-primary" data-open="#audioModal"><i class="ri-add-line"></i> Ajouter un audio</button>
        </div>
    </div>

    @include('partials.flash')

    @if ($imports->isNotEmpty())
        <div class="card mb-3">
            <div class="card-head">
                <span><i class="ri-download-cloud-2-line"></i> Imports en cours ou en échec <span class="badge">{{ $imports->count() }}</span></span>
                <span class="hint">Rechargez la page pour suivre l'avancement</span>
            </div>
            <div class="table-wrap">
                <table class="table stack">
                    <thead><tr><th>Lien</th><th>Catégorie</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($imports as $import)
                            <tr>
                                <td class="clip" data-label="Lien"><a href="{{ $import->source_url }}" target="_blank" rel="noopener">{{ $import->source_url }}</a></td>
                                <td data-label="Catégorie">{{ optional($import->category)->title ?? '—' }}</td>
                                <td data-label="Statut">
                                    @if ($import->status === 'failed')
                                        <span class="badge err">Échec</span>
                                        <div class="small muted">{{ $import->error }}</div>
                                    @else
                                        <span class="badge info"><span class="spin"></span> En traitement</span>
                                    @endif
                                </td>
                                <td class="actions-cell">
                                    <div class="btn-group">
                                        @if ($import->status === 'failed')
                                            <form class="inline" method="post" action="{{ url('/audios/imports/' . $import->id . '/retry') }}">
                                                @csrf
                                                <button class="btn btn-sm" type="submit" title="Relancer"><i class="ri-restart-line"></i> Relancer</button>
                                            </form>
                                        @endif
                                        <form class="inline" method="post" action="{{ url('/audios/imports/' . $import->id) }}" onsubmit="return confirm('Abandonner cet import ?')">
                                            @csrf @method('delete')
                                            <button class="btn btn-sm btn-danger btn-icon" type="submit" title="Abandonner"><i class="ri-delete-bin-6-line"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card">
        <form class="toolbar" method="get" action="/audios">
            <div class="search grow" style="max-width:360px"><i class="ri-search-line"></i><input class="input" type="search" name="q" value="{{ $q }}" placeholder="Titre ou slug…"></div>
            <select class="select" name="category" data-search data-placeholder="Toutes les catégories" data-allow-empty>
                <option value="">Toutes les catégories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" data-group="{{ $typeLabels[$category->type] ?? $category->type }}" @selected($categoryId === $category->id)>{{ $category->title }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Filtrer</button>
            @if ($q !== '' || $categoryId)
                <a class="btn btn-ghost" href="/audios">Réinitialiser</a>
            @endif
        </form>
        <div class="table-wrap">
            <table class="table stack">
                <thead>
                    <tr><th class="idx">#</th><th>Titre</th><th>Catégorie</th><th>Ajouté</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($audios as $audio)
                        <tr>
                            <td class="idx">{{ $audios->firstItem() + $loop->index }}</td>
                            <td data-label="Titre"><div class="title">{{ $audio->title }}</div><div class="slug">{{ $audio->slug }}</div></td>
                            <td data-label="Catégorie">
                                @if ($audio->category)
                                    <span>{{ $audio->category->title }} <span class="type-tag">· {{ $typeLabels[$audio->category->type] ?? $audio->category->type }}</span></span>
                                @else
                                    <span class="muted">Sans catégorie</span>
                                @endif
                            </td>
                            <td data-label="Ajouté" class="muted small nowrap">{{ optional($audio->created_at)->format('d/m/Y') ?? '—' }}</td>
                            <td class="actions-cell">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-icon" title="Modifier" data-open="#audioEditModal"
                                        data-action="/audios/{{ $audio->id }}" data-set-title="{{ $audio->title }}"
                                        data-set-category="{{ optional($audio->category)->slug }}"><i class="ri-edit-box-line"></i></button>
                                    @if ($isSuperAdmin)
                                        <form class="inline" method="post" action="{{ url('/audios/' . $audio->id) }}" onsubmit="return confirm('Supprimer « {{ addslashes($audio->title) }} » ?')">
                                            @csrf @method('delete')
                                            <button class="btn btn-sm btn-danger btn-icon" type="submit" title="Supprimer"><i class="ri-delete-bin-6-line"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="5">Aucun audio ne correspond à cette recherche.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $audios->links('partials.pagination') }}
    </div>
</div>

{{-- Ajout --}}
<x-modal id="audioModal" title="Ajouter un audio" size="lg" form="" action="{{ url('/audios') }}" :upload="true">
    <div class="fields-2">
        <div class="field">
            <label class="req" for="title">Titre</label>
            <input type="text" required name="title" class="input" id="title" />
        </div>
        <div class="field">
            <label class="req" for="category">Catégorie</label>
            <select class="select" name="category" id="category" required data-search data-placeholder="Choisir une catégorie">
                <option value="">Choisir une catégorie</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" data-group="{{ $typeLabels[$category->type] ?? $category->type }}">{{ $category->title }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="field">
        <label class="req" for="audio">Fichier audio (MP3)</label>
        <input class="input" type="file" id="audio" name="audio" accept="audio/*" required data-title-target="#title" />
        <div class="help">Le titre est déduit du nom du fichier si le champ est vide. Le fichier est compressé à {{ (int) config('services.xassaid.audio_bitrate', 96) }} kbps après l'envoi.</div>
    </div>
    <div class="upload-state"><div class="txt">Envoi du fichier…</div><div class="progress"><span style="width:0"></span></div></div>
    <div class="flash err upload-error"></div>
    <x-slot:footer>
        <button type="button" class="btn" data-close>Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </x-slot:footer>
</x-modal>

{{-- Import par liens --}}
<x-modal id="audioImportModal" title="Importer des audios depuis des liens" size="lg" form="" action="{{ url('/audios/import') }}">
    <div class="field">
        <label class="req" for="audioImportCategory">Catégorie</label>
        <select class="select" name="category_id" id="audioImportCategory" required data-search data-placeholder="Choisir une catégorie">
            <option value="">Choisir une catégorie</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" data-group="{{ $typeLabels[$category->type] ?? $category->type }}">{{ $category->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label class="req" for="audioImportLinks">Liens (un par ligne, 20 max)</label>
        <textarea name="links" id="audioImportLinks" class="textarea" rows="6" required placeholder="https://www.youtube.com/watch?v=…&#10;https://www.instagram.com/reel/…"></textarea>
        <div class="help">La piste audio de chaque vidéo est téléchargée, convertie en MP3 puis envoyée. Le titre reprend celui de la vidéo. Le traitement se fait en arrière-plan et les imports s'affichent en haut de la page.</div>
    </div>
    <x-slot:footer>
        <button type="button" class="btn" data-close>Annuler</button>
        <button type="submit" class="btn btn-primary">Lancer l'import</button>
    </x-slot:footer>
</x-modal>

{{-- Modification --}}
<x-modal id="audioEditModal" title="Modifier l'audio" size="lg" form="" method="put" :multipart="true">
    <div class="fields-2">
        <div class="field">
            <label class="req" for="audioEditTitle">Titre</label>
            <input type="text" required name="title" class="input" id="audioEditTitle" />
        </div>
        <div class="field">
            <label class="req" for="audioEditCategory">Catégorie</label>
            <select class="select" name="category" id="audioEditCategory" required data-search data-placeholder="Choisir une catégorie">
                <option value="">Choisir une catégorie</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" data-group="{{ $typeLabels[$category->type] ?? $category->type }}">{{ $category->title }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="field">
        <label for="audioEditFile">Remplacer le fichier audio</label>
        <input class="input" type="file" id="audioEditFile" name="audio" accept="audio/*" />
        <div class="help">Facultatif. Laissez vide pour conserver le fichier actuel.</div>
    </div>
    <x-slot:footer>
        <button type="button" class="btn" data-close>Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </x-slot:footer>
</x-modal>
@endsection

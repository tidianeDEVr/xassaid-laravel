@extends('base')

@section('title', 'Bibliothèque')

@section('content')
@php $isSuperAdmin = auth()->user()->isSuperAdmin(); @endphp
<div class="page">
    <div class="page-head">
        <div>
            <h1>Bibliothèque <span class="n">{{ number_format($files->total(), 0, ',', ' ') }}</span></h1>
            <div class="sub">Documents PDF proposés au téléchargement.</div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" data-open="#fileModal"><i class="ri-add-line"></i> Ajouter un PDF</button>
        </div>
    </div>

    @include('partials.flash')

    <div class="card">
        <form class="toolbar" method="get" action="/library">
            <div class="search grow" style="max-width:360px"><i class="ri-search-line"></i><input class="input" type="search" name="q" value="{{ $q }}" placeholder="Titre ou slug…"></div>
            <button class="btn" type="submit">Rechercher</button>
            @if ($q !== '')
                <a class="btn btn-ghost" href="/library">Réinitialiser</a>
            @endif
        </form>
        <div class="table-wrap">
            <table class="table stack">
                <thead>
                    <tr><th class="idx">#</th><th>Titre</th><th>Ajouté</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($files as $file)
                        <tr>
                            <td class="idx">{{ $files->firstItem() + $loop->index }}</td>
                            <td data-label="Titre"><div class="title">{{ $file->title }}</div><div class="slug">{{ $file->slug }}</div></td>
                            <td data-label="Ajouté" class="muted small nowrap">{{ optional($file->created_at)->format('d/m/Y') ?? '—' }}</td>
                            <td class="actions-cell">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-icon" title="Modifier" data-open="#fileEditModal"
                                        data-action="/library/{{ $file->id }}" data-set-title="{{ $file->title }}" data-set-slug="{{ $file->slug }}"><i class="ri-edit-box-line"></i></button>
                                    @if ($isSuperAdmin)
                                        <form class="inline" method="post" action="{{ url('/library/' . $file->id) }}" onsubmit="return confirm('Supprimer « {{ addslashes($file->title) }} » ?')">
                                            @csrf @method('delete')
                                            <button class="btn btn-sm btn-danger btn-icon" type="submit" title="Supprimer"><i class="ri-delete-bin-6-line"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="4">Aucun fichier ne correspond à cette recherche.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $files->links('partials.pagination') }}
    </div>
</div>

<x-modal id="fileModal" title="Ajouter un PDF" form="" action="{{ url('/library') }}" :upload="true">
    <div class="field">
        <label class="req" for="fileTitle">Titre</label>
        <input type="text" required name="title" class="input" id="fileTitle" />
    </div>
    <div class="field">
        <label class="req" for="fileUpload">Fichier PDF</label>
        <input class="input" type="file" id="fileUpload" name="file" accept="application/pdf" required data-title-target="#fileTitle" />
        <div class="help">Le titre est déduit du nom du fichier si le champ est vide.</div>
    </div>
    <div class="upload-state"><div class="txt">Envoi du fichier…</div><div class="progress"><span style="width:0"></span></div></div>
    <div class="flash err upload-error"></div>
    <x-slot:footer>
        <button type="button" class="btn" data-close>Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </x-slot:footer>
</x-modal>

<x-modal id="fileEditModal" title="Modifier le fichier" form="" method="put">
    <div class="field">
        <label class="req" for="fileEditTitle">Titre</label>
        <input type="text" required name="title" class="input" id="fileEditTitle" />
    </div>
    <div class="field">
        <label for="fileEditSlug">Slug</label>
        <input type="text" name="slug" class="input" id="fileEditSlug" />
        <div class="help">Identifiant dans l'adresse publique. Laissez vide pour le conserver.</div>
    </div>
    <x-slot:footer>
        <button type="button" class="btn" data-close>Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </x-slot:footer>
</x-modal>
@endsection

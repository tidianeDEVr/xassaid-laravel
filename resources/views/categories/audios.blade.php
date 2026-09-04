@extends('base')

@section('title', 'Catégories d\'audios')

@section('content')
@php
    $isSuperAdmin = auth()->user()->isSuperAdmin();
    $typeLabels = ['makk-gni' => 'Makk gni', 'kourels-yii' => 'Kourels yii', 'rajass-kat-yii' => 'Rajass kat yii', 'autres' => 'Autres'];
@endphp
<div class="page">
    <div class="page-head">
        <div>
            <h1>Catégories d'audios <span class="n">{{ $categories->total() }}</span></h1>
            <div class="sub">Regroupements du catalogue, avec image de couverture.</div>
        </div>
        <div class="actions">
            <a href="/audios" class="btn"><i class="ri-folder-music-line"></i> Audios</a>
            <button type="button" class="btn btn-primary" data-open="#categoryModal"><i class="ri-add-line"></i> Ajouter une catégorie</button>
        </div>
    </div>

    @include('partials.flash')

    <div class="card">
        <form class="toolbar" method="get" action="/categories/audios">
            <div class="search grow" style="max-width:360px"><i class="ri-search-line"></i><input class="input" type="search" name="q" value="{{ $q }}" placeholder="Titre ou slug…"></div>
            <select class="select w-auto" name="type">
                <option value="">Tous les types</option>
                @foreach ($typeLabels as $value => $label)
                    <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Filtrer</button>
            @if ($q !== '' || $type !== '')
                <a class="btn btn-ghost" href="/categories/audios">Réinitialiser</a>
            @endif
        </form>
        <div class="table-wrap">
            <table class="table stack">
                <thead>
                    <tr><th class="idx">#</th><th>Image</th><th>Titre</th><th>Type</th><th class="right">Audios</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td class="idx">{{ $categories->firstItem() + $loop->index }}</td>
                            <td data-label="Image">
                                @if ($category->coverImagePath)
                                    <a href="https://files.xassaid.com/images/{{ $category->coverImagePath }}" target="_blank" rel="noopener"><img class="thumb" src="https://files.xassaid.com/images/{{ $category->coverImagePath }}" alt=""></a>
                                @else
                                    <span class="thumb-empty" title="Pas d'image"><i class="ri-image-line"></i></span>
                                @endif
                            </td>
                            <td data-label="Titre"><div class="title">{{ $category->title }}</div><div class="slug">{{ $category->slug }}</div></td>
                            <td data-label="Type">{{ $typeLabels[$category->type] ?? $category->type }}</td>
                            <td data-label="Audios" class="right"><a href="/audios?category={{ $category->id }}">{{ $category->audios_count }}</a></td>
                            <td class="actions-cell">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-icon" title="Modifier" data-open="#categoryEditModal"
                                        data-action="/categories/audios/{{ $category->id }}" data-set-title="{{ $category->title }}" data-set-type="{{ $category->type }}"><i class="ri-edit-box-line"></i></button>
                                    @if ($isSuperAdmin)
                                        <form class="inline" method="post" action="/categories/audios/{{ $category->id }}" onsubmit="return confirm('Supprimer la catégorie « {{ addslashes($category->title) }} » ?')">
                                            @csrf @method('delete')
                                            <button class="btn btn-sm btn-danger btn-icon" type="submit" title="Supprimer"><i class="ri-delete-bin-6-line"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="6">Aucune catégorie ne correspond à cette recherche.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $categories->links('partials.pagination') }}
    </div>
</div>

<x-modal id="categoryModal" title="Ajouter une catégorie" form="" action="/categories/audios" :multipart="true">
    <div class="field">
        <label class="req" for="categoryTitle">Titre</label>
        <input type="text" name="title" required class="input" id="categoryTitle" />
    </div>
    <div class="field">
        <label class="req" for="categoryType">Type</label>
        <select class="select" name="type" id="categoryType" required>
            <option value="">Choisir le type</option>
            @foreach ($typeLabels as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="coverImage">Image de couverture</label>
        <input class="input" type="file" id="coverImage" name="coverImage" accept="image/*" />
        <div class="help">Redimensionnée automatiquement (1200 px max).</div>
    </div>
    <x-slot:footer>
        <button type="button" class="btn" data-close>Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </x-slot:footer>
</x-modal>

<x-modal id="categoryEditModal" title="Modifier la catégorie" form="" method="put" :multipart="true">
    <div class="field">
        <label class="req" for="categoryEditTitle">Titre</label>
        <input type="text" name="title" required class="input" id="categoryEditTitle" />
    </div>
    <div class="field">
        <label class="req" for="categoryEditType">Type</label>
        <select class="select" name="type" id="categoryEditType" required>
            @foreach ($typeLabels as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="categoryEditCover">Nouvelle image de couverture</label>
        <input class="input" type="file" id="categoryEditCover" name="coverImage" accept="image/*" />
        <div class="help">Facultatif. Laissez vide pour conserver l'image actuelle.</div>
    </div>
    <x-slot:footer>
        <button type="button" class="btn" data-close>Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </x-slot:footer>
</x-modal>
@endsection

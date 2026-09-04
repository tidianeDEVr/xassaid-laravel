@extends('base')

@section('title', 'Articles')

@section('content')
@php $isSuperAdmin = auth()->user()->isSuperAdmin(); @endphp
<div class="page">
    <div class="page-head">
        <div>
            <h1>Articles <span class="n">{{ $articles->total() }}</span></h1>
            <div class="sub">Articles publiés sur le site, avec leurs balises de référencement.</div>
        </div>
        <div class="actions">
            <a href="/articles/create" class="btn btn-primary"><i class="ri-quill-pen-line"></i> Nouvel article</a>
        </div>
    </div>

    @include('partials.flash')

    <div class="card">
        <form class="toolbar" method="get" action="/articles">
            <div class="search grow" style="max-width:360px"><i class="ri-search-line"></i><input class="input" type="search" name="q" value="{{ $q }}" placeholder="Titre ou slug…"></div>
            <button class="btn" type="submit">Rechercher</button>
            @if ($q !== '')
                <a class="btn btn-ghost" href="/articles">Réinitialiser</a>
            @endif
        </form>
        <div class="table-wrap">
            <table class="table stack">
                <thead>
                    <tr><th class="idx">#</th><th>Image</th><th>Titre</th><th>Publié</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($articles as $article)
                        <tr>
                            <td class="idx">{{ $articles->firstItem() + $loop->index }}</td>
                            <td data-label="Image">
                                @if ($article->image)
                                    <a href="https://files.xassaid.com/images/{{ $article->image }}" target="_blank" rel="noopener"><img class="thumb" src="https://files.xassaid.com/images/{{ $article->image }}" alt=""></a>
                                @else
                                    <span class="thumb-empty" title="Pas d'image"><i class="ri-image-line"></i></span>
                                @endif
                            </td>
                            <td data-label="Titre"><a class="title" href="{{ url('/articles/' . $article->id . '/edit') }}">{{ $article->title }}</a><div class="slug">{{ $article->slug }}</div></td>
                            <td data-label="Publié" class="muted small nowrap">{{ optional($article->created_at)->format('d/m/Y') ?? '—' }}</td>
                            <td class="actions-cell">
                                <div class="btn-group">
                                    <a class="btn btn-sm btn-icon" title="Modifier" href="{{ url('/articles/' . $article->id . '/edit') }}"><i class="ri-edit-box-line"></i></a>
                                    @if ($isSuperAdmin)
                                        <form class="inline" method="post" action="{{ url('/articles/' . $article->id) }}" onsubmit="return confirm('Supprimer l\'article « {{ addslashes($article->title) }} » ?')">
                                            @csrf @method('delete')
                                            <button class="btn btn-sm btn-danger btn-icon" type="submit" title="Supprimer"><i class="ri-delete-bin-6-line"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="5">Aucun article ne correspond à cette recherche.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $articles->links('partials.pagination') }}
    </div>
</div>
@endsection

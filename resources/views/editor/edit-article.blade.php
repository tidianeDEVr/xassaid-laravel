@extends('base')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Modifier l'article</h1>

    @if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ url('/articles/' . $article->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('put')
        <div class="mb-3">
            <label for="title" class="form-label">Titre de l'article<span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $article->title) }}" required>
        </div>

        <div class="mb-3">
            <label for="image" class="form-label">Image de l'article</label>
            <input type="file" class="form-control" id="image" name="image" accept="image/*">
            @if ($article->image)
                <div class="mt-2">
                    <img class="img-thumbnail" width="120" src="https://files.xassaid.com/images/{{ $article->image }}" alt="{{ $article->title }}">
                </div>
            @endif
        </div>

        <div class="mb-3">
            <label for="seo_keywords" class="form-label">Mots-clés (SEO Keywords)<span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="seo_keywords" name="seo_keywords"
                value="{{ old('seo_keywords', $article->seo_keywords) }}" placeholder="mot-clé 1, mot-clé 2, mot-clé 3" required>
        </div>

        <div class="mb-3">
            <label for="seo_title" class="form-label">Balise SEO - Titre<span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="seo_title" name="seo_title"
                value="{{ old('seo_title', $article->seo_title) }}" placeholder="Titre pour le référencement SEO" required>
        </div>

        <div class="mb-3">
            <label for="seo_description" class="form-label">Balise SEO - Description<span class="text-danger">*</span></label>
            <textarea class="form-control" id="seo_description" name="seo_description" rows="3" required>{{ old('seo_description', $article->seo_description) }}</textarea>
        </div>

        <div class="mb-3">
            <label for="content" class="form-label">Contenu de l'article<span class="text-danger">*</span></label>
            <div id="editor" style="height: 300px;"></div>
            <input type="hidden" name="content" id="content" value="{{ old('content', $article->content) }}">
        </div>

        <button type="submit" class="btn btn-lg btn-dark mb-5">Mettre à jour</button>
    </form>
</div>
@endsection

@section('styles')
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
@endsection

@section('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
    let quill = new Quill('#editor', {
        theme: 'snow',
        placeholder: 'Rédigez le contenu de votre article ici...',
    });

    let content = document.getElementById('content');
    let initialContent = @json(old('content', $article->content));
    quill.root.innerHTML = initialContent || '';
    content.value = initialContent || '';

    quill.on('text-change', function() {
        content.value = quill.root.innerHTML;
    });
</script>
@endsection

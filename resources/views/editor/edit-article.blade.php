@extends('base')

@section('title', 'Modifier l\'article')

@section('content')
<div class="page">
    <div class="page-head">
        <div>
            <h1>Modifier l'article</h1>
            <div class="sub"><a href="/articles">← Retour aux articles</a></div>
        </div>
    </div>

    @include('partials.flash')

    <form action="/articles/{{ $article->id }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('put')
        @include('editor.partials.article-form', ['article' => $article])
    </form>
</div>
@endsection

@section('styles')
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
@endsection

@section('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
    const quill = new Quill('#editor', { theme: 'snow', placeholder: 'Rédigez le contenu de l\'article…' });
    const content = document.getElementById('content');
    const initialContent = @json(old('content', $article->content));
    quill.root.innerHTML = initialContent || '';
    content.value = initialContent || '';
    quill.on('text-change', () => { content.value = quill.root.innerHTML; });
</script>
@endsection

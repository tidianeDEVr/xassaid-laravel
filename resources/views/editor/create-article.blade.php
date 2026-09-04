@extends('base')

@section('title', 'Nouvel article')

@section('content')
<div class="page">
    <div class="page-head">
        <div>
            <h1>Nouvel article</h1>
            <div class="sub"><a href="/articles">← Retour aux articles</a></div>
        </div>
    </div>

    @include('partials.flash')

    <form action="{{ url('/articles/create') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('editor.partials.article-form', ['article' => null])
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
    quill.on('text-change', () => { content.value = quill.root.innerHTML; });
</script>
@endsection

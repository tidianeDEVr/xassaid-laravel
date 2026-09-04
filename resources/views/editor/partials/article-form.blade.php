{{-- Champs communs création / édition d'article. $article : null en création. --}}
<div class="grid grid-3">
    <div class="card span-2">
        <div class="card-head">Contenu</div>
        <div class="card-body">
            <div class="field">
                <label class="req" for="title">Titre</label>
                <input type="text" class="input" id="title" name="title" value="{{ old('title', $article?->title) }}" required>
            </div>
            <div class="field">
                <label class="req" for="editor">Texte de l'article</label>
                <div id="editor" style="height: 380px; background: #fff;"></div>
                <input type="hidden" name="content" id="content" value="{{ old('content', $article?->content) }}">
            </div>
        </div>
    </div>
    <div class="vstack">
        <div class="card">
            <div class="card-head">Image</div>
            <div class="card-body">
                @if ($article?->image)
                    <img class="thumb" style="width:100%;height:150px;margin-bottom:10px" src="https://files.xassaid.com/images/{{ $article->image }}" alt="">
                @endif
                <div class="field">
                    <label class="{{ $article ? '' : 'req' }}" for="image">{{ $article ? 'Remplacer l\'image' : 'Image de l\'article' }}</label>
                    <input type="file" class="input" id="image" name="image" accept="image/*" {{ $article ? '' : 'required' }}>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-head">Référencement</div>
            <div class="card-body">
                <div class="field">
                    <label class="req" for="seo_title">Titre de la page</label>
                    <input type="text" class="input" id="seo_title" name="seo_title" value="{{ old('seo_title', $article?->seo_title) }}" required>
                </div>
                <div class="field">
                    <label class="req" for="seo_keywords">Mots-clés</label>
                    <input type="text" class="input" id="seo_keywords" name="seo_keywords" value="{{ old('seo_keywords', $article?->seo_keywords) }}" placeholder="mot-clé 1, mot-clé 2" required>
                </div>
                <div class="field">
                    <label class="req" for="seo_description">Description</label>
                    <textarea class="textarea" id="seo_description" name="seo_description" rows="4" required>{{ old('seo_description', $article?->seo_description) }}</textarea>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">{{ $article ? 'Enregistrer les modifications' : 'Publier l\'article' }}</button>
    </div>
</div>

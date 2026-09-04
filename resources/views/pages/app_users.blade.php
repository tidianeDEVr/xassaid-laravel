@extends('base')

@section('title', 'Comptes app')

@section('content')
<div class="page page-wide">
    <div class="page-head">
        <div>
            <h1>Comptes de l'application <span class="n">{{ $appUsers->total() }}</span></h1>
            <div class="sub">Utilisateurs du feed vidéo. Un compte certifié publie sans validation.</div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" data-open="#appUserModal"><i class="ri-user-add-line"></i> Créer un compte</button>
        </div>
    </div>

    @include('partials.flash')

    <div class="card">
        <form class="toolbar" method="get" action="/app-users">
            <div class="search grow" style="max-width:360px"><i class="ri-search-line"></i><input class="input" type="search" name="q" value="{{ $q }}" placeholder="Pseudo ou nom affiché…"></div>
            <button class="btn" type="submit">Rechercher</button>
            @if ($q !== '')
                <a class="btn btn-ghost" href="/app-users">Réinitialiser</a>
            @endif
        </form>
        <div class="table-wrap">
            <table class="table stack">
                <thead>
                    <tr><th class="idx">#</th><th>Compte</th><th class="right">Vidéos</th><th class="right">Abonnés</th><th>Inscrit le</th><th>Certification</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($appUsers as $appUser)
                        <tr>
                            <td class="idx">{{ $appUsers->firstItem() + $loop->index }}</td>
                            <td data-label="Compte">
                                <div class="row" style="flex-wrap:nowrap">
                                    @if ($appUser->avatar_path)
                                        <img class="avatar" src="{{ $appUser->avatarUrl() }}" alt="" />
                                    @else
                                        <span class="thumb-empty" style="width:36px;height:36px;border-radius:50%"><i class="ri-user-line"></i></span>
                                    @endif
                                    <div><div class="title">{{ $appUser->display_name }}</div><div class="slug">{{ '@' . $appUser->username }}</div></div>
                                </div>
                            </td>
                            <td data-label="Vidéos" class="right">{{ $appUser->videos_count }}</td>
                            <td data-label="Abonnés" class="right">{{ $appUser->followers_count }}</td>
                            <td data-label="Inscrit le" class="muted small nowrap">{{ $appUser->created_at->format('d/m/Y') }}</td>
                            <td data-label="Certification">
                                <form class="inline" method="post" action="/app-users/{{ $appUser->id }}/certify"
                                    onsubmit="return confirm('{{ $appUser->is_certified ? 'Retirer la certification de @' . $appUser->username . ' ?' : 'Certifier @' . $appUser->username . ' ? Ses vidéos seront publiées sans validation.' }}')">
                                    @csrf @method('put')
                                    <button type="submit" class="btn btn-sm {{ $appUser->is_certified ? 'btn-accent' : '' }}">
                                        <i class="ri-verified-badge-{{ $appUser->is_certified ? 'fill' : 'line' }}"></i> {{ $appUser->is_certified ? 'Certifié' : 'Certifier' }}
                                    </button>
                                </form>
                            </td>
                            <td class="actions-cell">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm" data-open="#passwordModal" data-action="/app-users/{{ $appUser->id }}/password" data-modal-title="Mot de passe de {{ '@' . $appUser->username }}"><i class="ri-lock-password-line"></i> Mot de passe</button>
                                    <button type="button" class="btn btn-sm" data-open="#avatarModal" data-action="/app-users/{{ $appUser->id }}/avatar" data-modal-title="Avatar de {{ '@' . $appUser->username }}"><i class="ri-image-line"></i> Avatar</button>
                                    <form class="inline" method="post" action="/app-users/{{ $appUser->id }}"
                                        onsubmit="return confirm('Supprimer définitivement {{ '@' . $appUser->username }} ? Ses {{ $appUser->videos_count }} vidéo(s), commentaires et abonnements seront effacés, fichiers compris. Action irréversible.')">
                                        @csrf @method('delete')
                                        <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Supprimer"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="7">Aucun compte ne correspond à cette recherche.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $appUsers->links('partials.pagination') }}
    </div>
</div>

<x-modal id="appUserModal" title="Créer un compte" form="" action="/app-users" :multipart="true">
    <div class="row mb-2" style="flex-wrap:nowrap;align-items:flex-start">
        <img id="appUserAvatarPreview" class="avatar" style="width:56px;height:56px;display:none" src="" alt="" />
        <div class="field grow">
            <label for="appUserAvatar">Avatar</label>
            <input type="file" name="avatar" id="appUserAvatar" class="input" accept="image/jpeg,image/png,image/webp" data-preview="#appUserAvatarPreview" />
            <div class="help">Facultatif. Recadré en carré 600×600, comme depuis l'application.</div>
        </div>
    </div>
    <div class="field">
        <label class="req" for="appUserUsername">Pseudo</label>
        <input type="text" required name="username" id="appUserUsername" class="input" pattern="[a-z0-9_.]{3,30}" placeholder="ex : kourel.treviso" />
        <div class="help">Unique, en minuscules : lettres, chiffres, point et tiret bas (3 à 30 caractères).</div>
    </div>
    <div class="field">
        <label class="req" for="appUserDisplayName">Nom affiché</label>
        <input type="text" required name="display_name" id="appUserDisplayName" class="input" />
    </div>
    <div class="field">
        <label class="req" for="appUserPassword">Mot de passe</label>
        <input type="text" required minlength="6" name="password" id="appUserPassword" class="input" autocomplete="off" />
        <div class="help">6 caractères minimum.</div>
    </div>
    <label class="check"><input type="checkbox" name="is_certified" value="1" /> Compte certifié (publie sans validation)</label>
    <x-slot:footer>
        <button type="button" class="btn" data-close>Annuler</button>
        <button type="submit" class="btn btn-primary">Créer le compte</button>
    </x-slot:footer>
</x-modal>

<x-modal id="passwordModal" title="Changer le mot de passe" size="sm" form="" method="put">
    <div class="field">
        <label class="req" for="newPassword">Nouveau mot de passe</label>
        <input type="text" required minlength="6" name="password" id="newPassword" class="input" autocomplete="off" />
        <div class="help">Les sessions mobiles de ce compte seront déconnectées.</div>
    </div>
    <x-slot:footer>
        <button type="button" class="btn" data-close>Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </x-slot:footer>
</x-modal>

<x-modal id="avatarModal" title="Changer l'avatar" size="sm" form="" :multipart="true">
    <div class="row" style="flex-wrap:nowrap;align-items:flex-start">
        <img id="avatarModalPreview" class="avatar" style="width:56px;height:56px;display:none" src="" alt="" />
        <div class="field grow">
            <input type="file" required name="avatar" id="avatarModalInput" class="input" accept="image/jpeg,image/png,image/webp" data-preview="#avatarModalPreview" />
            <div class="help">Recadré en carré 600×600. L'ancien avatar est supprimé.</div>
        </div>
    </div>
    <x-slot:footer>
        <button type="button" class="btn" data-close>Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </x-slot:footer>
</x-modal>
@endsection

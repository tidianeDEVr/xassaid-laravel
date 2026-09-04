@extends('base')

@section('title', 'Utilisateurs')

@section('content')
<div class="page">
    <div class="page-head">
        <div>
            <h1>Utilisateurs <span class="n">{{ $users->total() }}</span></h1>
            <div class="sub">Comptes ayant accès à ce backoffice.</div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" data-open="#userModal"><i class="ri-user-add-line"></i> Ajouter un administrateur</button>
        </div>
    </div>

    @include('partials.flash')

    <div class="card">
        <form class="toolbar" method="get" action="/users">
            <div class="search grow" style="max-width:360px"><i class="ri-search-line"></i><input class="input" type="search" name="q" value="{{ $q }}" placeholder="Nom ou e-mail…"></div>
            <button class="btn" type="submit">Rechercher</button>
            @if ($q !== '')
                <a class="btn btn-ghost" href="/users">Réinitialiser</a>
            @endif
        </form>
        <div class="table-wrap">
            <table class="table stack">
                <thead>
                    <tr><th class="idx">#</th><th>Nom</th><th>E-mail</th><th>Rôle</th><th>Créé le</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="idx">{{ $users->firstItem() + $loop->index }}</td>
                            <td data-label="Nom" class="title">{{ $user->name }} @if (auth()->id() === $user->id)<span class="badge">vous</span>@endif</td>
                            <td data-label="E-mail">{{ $user->email }}</td>
                            <td data-label="Rôle">{{ $user->isSuperAdmin() ? 'Super administrateur' : 'Administrateur' }}</td>
                            <td data-label="Créé le" class="muted small nowrap">{{ $user->created_at->translatedFormat('d M Y') }}</td>
                            <td class="actions-cell">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-icon" title="Modifier" data-open="#userEditModal"
                                        data-action="/users/{{ $user->id }}" data-set-name="{{ $user->name }}" data-set-email="{{ $user->email }}" data-set-password=""><i class="ri-edit-box-line"></i></button>
                                    @if (auth()->id() !== $user->id)
                                        <form class="inline" method="post" action="{{ url('/users/' . $user->id) }}" onsubmit="return confirm('Supprimer le compte de {{ addslashes($user->name) }} ?')">
                                            @csrf @method('delete')
                                            <button class="btn btn-sm btn-danger btn-icon" type="submit" title="Supprimer"><i class="ri-delete-bin-6-line"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="6">Aucun utilisateur ne correspond à cette recherche.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $users->links('partials.pagination') }}
    </div>
</div>

<x-modal id="userModal" title="Ajouter un administrateur" form="" action="{{ url('/users') }}">
    <div class="fields-2">
        <div class="field">
            <label class="req" for="firstname">Prénom</label>
            <input type="text" required name="firstname" class="input" id="firstname" autocomplete="off" />
        </div>
        <div class="field">
            <label class="req" for="lastname">Nom</label>
            <input type="text" required name="lastname" class="input" id="lastname" autocomplete="off" />
        </div>
    </div>
    <div class="field">
        <label class="req" for="email">Adresse e-mail</label>
        <input type="email" required name="email" class="input" id="email" autocomplete="off" />
    </div>
    <div class="field">
        <label class="req" for="password">Mot de passe</label>
        <input type="password" required minlength="4" name="password" class="input" id="password" autocomplete="new-password" />
    </div>
    <x-slot:footer>
        <button type="button" class="btn" data-close>Annuler</button>
        <button type="submit" class="btn btn-primary">Créer le compte</button>
    </x-slot:footer>
</x-modal>

<x-modal id="userEditModal" title="Modifier l'utilisateur" form="" method="put">
    <div class="fields-2">
        <div class="field">
            <label class="req" for="userEditName">Nom complet</label>
            <input type="text" required name="name" class="input" id="userEditName" />
        </div>
        <div class="field">
            <label class="req" for="userEditEmail">E-mail</label>
            <input type="email" required name="email" class="input" id="userEditEmail" />
        </div>
    </div>
    <div class="field">
        <label for="userEditPassword">Nouveau mot de passe</label>
        <input type="password" name="password" class="input" id="userEditPassword" autocomplete="new-password" />
        <div class="help">Laissez vide pour conserver le mot de passe actuel.</div>
    </div>
    <x-slot:footer>
        <button type="button" class="btn" data-close>Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </x-slot:footer>
</x-modal>
@endsection

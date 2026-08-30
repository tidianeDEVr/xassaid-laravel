@extends('base')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-3 gap-2">
      <h1>Comptes de l'application</h1>
      <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#appUserModal">
        Créer un compte
      </button>
    </div>
    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="table-responsive">
      <table id="appUsersTable" class="table table-bordered align-middle" style="width: 100%">
        <thead>
          <tr>
            <th>N*</th>
            <th>Avatar</th>
            <th>Pseudo</th>
            <th>Nom affiché</th>
            <th>Vidéos</th>
            <th>Abonnés</th>
            <th>Inscrit le</th>
            <th>Certifié</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($appUsers as $index => $appUser)
          <tr>
            <td>{{ $index + 1 }}</td>
            <td>
              @if ($appUser->avatar_path)
                <img src="{{ $appUser->avatarUrl() }}" alt="avatar"
                     style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;" />
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
            <td>{{ '@' . $appUser->username }}</td>
            <td>{{ $appUser->display_name }}</td>
            <td>{{ $appUser->videos_count }}</td>
            <td>{{ $appUser->followers_count }}</td>
            <td>{{ $appUser->created_at->format('d/m/Y') }}</td>
            <td>
              <form method="post" action="{{ url('/app-users/' . $appUser->id . '/certify') }}">
                @csrf
                @method('put')
                <button type="submit"
                  class="btn btn-sm {{ $appUser->is_certified ? 'btn-success' : 'btn-outline-secondary' }}"
                  onclick="return confirm('{{ $appUser->is_certified
                    ? 'Retirer la certification de @' . $appUser->username . ' ?'
                    : 'Certifier @' . $appUser->username . ' ? Ses vidéos seront publiées sans validation.' }}')">
                  <i class="ri-verified-badge-{{ $appUser->is_certified ? 'fill' : 'line' }}"></i>
                  {{ $appUser->is_certified ? 'Certifié' : 'Certifier' }}
                </button>
              </form>
            </td>
            <td>
              <div class="d-flex gap-1">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                  data-bs-target="#passwordModal" data-id="{{ $appUser->id }}"
                  data-username="{{ $appUser->username }}">
                  <i class="ri-lock-password-line"></i> Mot de passe
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                  data-bs-target="#avatarModal" data-id="{{ $appUser->id }}"
                  data-username="{{ $appUser->username }}">
                  <i class="ri-image-line"></i> Avatar
                </button>
                <form method="post" action="{{ url('/app-users/' . $appUser->id) }}">
                  @csrf
                  @method('delete')
                  <button type="submit" class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('Supprimer définitivement {{ '@' . $appUser->username }} ? Ses {{ $appUser->videos_count }} vidéo(s), commentaires et abonnements seront effacés, fichiers compris. Action irréversible.')">
                    <i class="ri-delete-bin-line"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
</div>

<!-- Création d'un compte app -->
<div class="modal fade" id="appUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="{{ url('/app-users') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h1 class="modal-title fs-5">Créer un compte</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3 d-flex align-items-center gap-3">
            <img id="appUserAvatarPreview" src="" alt=""
                 style="width: 56px; height: 56px; object-fit: cover; border-radius: 50%; display: none;" />
            <div class="flex-grow-1">
              <label class="form-label" for="appUserAvatar">Avatar (optionnel)</label>
              <input type="file" name="avatar" id="appUserAvatar" class="form-control"
                accept="image/jpeg,image/png,image/webp" />
              <div class="form-text">Recadré en carré 600x600, comme depuis l'application.</div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><span class="text-danger">*</span>Pseudo (unique, minuscules)</label>
            <input type="text" required name="username" class="form-control"
              pattern="[a-z0-9_.]{3,30}" placeholder="ex : kourel.treviso" />
          </div>
          <div class="mb-3">
            <label class="form-label"><span class="text-danger">*</span>Nom affiché</label>
            <input type="text" required name="display_name" class="form-control" />
          </div>
          <div class="mb-3">
            <label class="form-label"><span class="text-danger">*</span>Mot de passe (6 caractères min.)</label>
            <input type="text" required minlength="6" name="password" class="form-control" />
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_certified" value="1" id="appUserCertified" />
            <label class="form-check-label" for="appUserCertified">
              Compte certifié (publie sans validation)
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-dark">Créer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Changement de mot de passe -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" id="passwordForm">
        @csrf
        @method('put')
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="passwordModalTitle">Changer le mot de passe</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-1">
            <label class="form-label"><span class="text-danger">*</span>Nouveau mot de passe</label>
            <input type="text" required minlength="6" name="password" class="form-control" />
          </div>
          <small class="text-muted">Les sessions mobiles de ce compte seront déconnectées.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-dark">Mettre à jour</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Changement d'avatar -->
<div class="modal fade" id="avatarModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" id="avatarForm" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="avatarModalTitle">Changer l'avatar</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="d-flex align-items-center gap-3">
            <img id="avatarModalPreview" src="" alt=""
                 style="width: 56px; height: 56px; object-fit: cover; border-radius: 50%; display: none;" />
            <div class="flex-grow-1">
              <input type="file" required name="avatar" id="avatarModalInput" class="form-control"
                accept="image/jpeg,image/png,image/webp" />
              <div class="form-text">Recadré en carré 600x600. L'ancien avatar est supprimé.</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-dark">Mettre à jour</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script defer>
    $(document).ready(function () {
      $("#appUsersTable").DataTable({ order: [] });
    });

    const passwordModal = document.getElementById("passwordModal");
    if (passwordModal) {
      passwordModal.addEventListener("show.bs.modal", function (event) {
        const button = event.relatedTarget;
        document.getElementById("passwordForm").action =
          `/app-users/${button.getAttribute("data-id")}/password`;
        document.getElementById("passwordModalTitle").textContent =
          `Mot de passe de @${button.getAttribute("data-username")}`;
      });
    }

    const avatarModal = document.getElementById("avatarModal");
    if (avatarModal) {
      avatarModal.addEventListener("show.bs.modal", function (event) {
        const button = event.relatedTarget;
        document.getElementById("avatarForm").action =
          `/app-users/${button.getAttribute("data-id")}/avatar`;
        document.getElementById("avatarModalTitle").textContent =
          `Avatar de @${button.getAttribute("data-username")}`;
      });
    }

    // Aperçu de l'image choisie : le recadrage carré définitif est fait côté
    // serveur, l'aperçu se contente de montrer le fichier retenu.
    function bindAvatarPreview(inputId, previewId) {
      const input = document.getElementById(inputId);
      if (!input) return;
      const preview = document.getElementById(previewId);
      input.addEventListener("change", function () {
        const file = this.files && this.files[0];
        if (preview.src) {
          URL.revokeObjectURL(preview.src);
        }
        preview.src = file ? URL.createObjectURL(file) : "";
        preview.style.display = file ? "block" : "none";
      });
    }

    bindAvatarPreview("appUserAvatar", "appUserAvatarPreview");
    bindAvatarPreview("avatarModalInput", "avatarModalPreview");
</script>
@endsection

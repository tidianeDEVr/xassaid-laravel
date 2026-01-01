@extends('base')

@section('content')
<div class="container py-4">
  <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-3 gap-2">
    <h1>Utilisateurs</h1>
    <button
      type="button"
      class="btn btn-dark"
      data-bs-toggle="modal"
      data-bs-target="#userModal"
    >
      Ajouter un nouveau admin
    </button>
  </div>

  <div
      class="modal fade"
      id="userModal"
      tabindex="-1"
      aria-labelledby="userModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form
            method="post"
            action="{{url('/users')}}"
          >
          @csrf
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="userModalLabel">
                Ajouter d'un nouveau admin
              </h1>
              <button
                type="button"
                class="btn-close"
                data-bs-dismiss="modal"
                aria-label="Close"
              ></button>
            </div>
            <div class="modal-body">
              <div class="row g-4 mb-3">
                <div class="col">
                  <label for="firstname" class="form-label"
                    ><span class="text-danger">*</span>Prénom</label
                  >
                  <input
                    type="text"
                    required
                    name="firstname"
                    class="form-control"
                    id="firstname"
                    placeholder="Entrez le prénom"
                  />
                </div>
                <div class="col">
                  <label for="lastname" class="form-label"
                    ><span class="text-danger">*</span>Nom</label
                  >
                  <input
                    type="text"
                    required
                    name="lastname"
                    class="form-control"
                    id="lastname"
                    placeholder="Entrez le nom"
                  />
                </div>
              </div>
              <div class="mb-3">
                <label for="email" class="form-label"
                    ><span class="text-danger">*</span>Adresse e-mail</label
                  >
                  <input
                    type="email"
                    required
                    name="email"
                    class="form-control"
                    id="email"
                    placeholder="Entrez l'adresse e-mail"
                  />
              </div>
              <div class="mb-3">
                <label for="password" class="form-label"
                    ><span class="text-danger">*</span>Mot de passe</label
                  >
                  <input
                    type="password"
                    required
                    minlength="4"
                    name="password"
                    class="form-control"
                    id="password"
                    placeholder="Entrez le mot de passe"
                  />
              </div>
            </div>
            <div class="modal-footer">
              <button
                type="button"
                class="btn btn-secondary"
                data-bs-dismiss="modal"
              >
                Annuler
              </button>
              <button type="submit" class="btn btn-dark">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    </div>

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

  <div class="table-responsive">
    <table id="users" class="table table-bordered" style="width: 100%">
      <thead>
        <tr>
          <th>N*</th>
          <th>Nom complet</th>
          <th>E-mail</th>
          <th>Ajouter le</th>
          <th>Modifier le</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($users as $index=>$user)
        <tr>
          <td>{{$index+1}}</td>
          <td>{{$user->name}}</td>
          <td>{{$user->email}}</td>
          <td>{{ $user->created_at->translatedFormat('d F Y, H:i') }}</td>
          <td>{{$user->updated_at->translatedFormat('d F Y, H:i')}}</td>
          <td>
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                data-bs-target="#userEditModal" data-id="{{ $user->id }}"
                data-name="{{ $user->name }}" data-email="{{ $user->email }}">
                <i class="ri-edit-box-line"></i>
              </button>
              <form method="post" action="{{ url('/users/' . $user->id) }}"
                onsubmit="return confirm('Confirmer la suppression ?')">
                @csrf
                @method('delete')
                <button class="btn btn-sm btn-outline-danger" type="submit" @if(auth()->id() === $user->id) disabled @endif>
                  <i class="ri-delete-bin-6-line"></i>
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

<div class="modal fade" id="userEditModal" tabindex="-1" aria-labelledby="userEditModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" id="userEditForm">
        @csrf
        @method('put')
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="userEditModalLabel">
            Modifier un utilisateur
          </h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-4 mb-3">
            <div class="col">
              <label for="userEditName" class="form-label"><span class="text-danger">*</span>Nom complet</label>
              <input type="text" required name="name" class="form-control" id="userEditName" />
            </div>
            <div class="col">
              <label for="userEditEmail" class="form-label"><span class="text-danger">*</span>E-mail</label>
              <input type="email" required name="email" class="form-control" id="userEditEmail" />
            </div>
          </div>
          <div class="mb-3">
            <label for="userEditPassword" class="form-label">Mot de passe (optionnel)</label>
            <input type="password" name="password" class="form-control" id="userEditPassword"
              placeholder="Laisser vide pour ne pas changer" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Annuler
          </button>
          <button type="submit" class="btn btn-dark">Mettre à jour</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
    $(document).ready(function () {
        var dataTable = $("#users").DataTable();
    });

    const userEditModal = document.getElementById("userEditModal");
    if (userEditModal) {
      userEditModal.addEventListener("show.bs.modal", function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute("data-id");
        const name = button.getAttribute("data-name");
        const email = button.getAttribute("data-email");
        const form = document.getElementById("userEditForm");

        form.action = `/users/${id}`;
        document.getElementById("userEditName").value = name || "";
        document.getElementById("userEditEmail").value = email || "";
        document.getElementById("userEditPassword").value = "";
      });
    }
</script>
@endsection

@extends('base')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-3 gap-2">
      <h1>Bibliothèque</h1>
      <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#fileModal">
        Ajouter un fichier
      </button>
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
      <table id="books" class="table table-bordered" style="width: 100%">
        <thead>
          <tr>
            <th>N*</th>
            <th>Titre</th>
            <th>Slug</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($files as $index=>$file)
          <tr>
            <td>{{$index+1}}</td>
            <td>{{$file->title}}</td>
            <td>{{$file->slug}}</td>
            <td>
              <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                  data-bs-target="#fileEditModal" data-id="{{ $file->id }}"
                  data-title="{{ $file->title }}" data-slug="{{ $file->slug }}">
                  <i class="ri-edit-box-line"></i>
                </button>
                <form method="post" action="{{ url('/library/' . $file->id) }}"
                  onsubmit="return confirm('Confirmer la suppression ?')">
                  @csrf
                  @method('delete')
                  <button class="btn btn-sm btn-outline-danger" type="submit">
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

<div class="modal fade" id="fileModal" tabindex="-1" aria-labelledby="fileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" enctype="multipart/form-data" action="{{ url('/library') }}">
        @csrf
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="fileModalLabel">
            Ajouter un fichier
          </h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-4 mb-3">
            <div class="col">
              <label for="fileTitle" class="form-label"><span class="text-danger">*</span>Titre</label>
              <input type="text" required name="title" class="form-control" id="fileTitle" />
            </div>
          </div>
          <div class="mb-3">
            <label for="fileUpload" class="form-label"><span class="text-danger">*</span>Fichier PDF</label>
            <input class="form-control" type="file" id="fileUpload" name="file" accept="application/pdf" required />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Annuler
          </button>
          <button type="submit" class="btn btn-dark">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="fileEditModal" tabindex="-1" aria-labelledby="fileEditModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" id="fileEditForm">
        @csrf
        @method('put')
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="fileEditModalLabel">
            Modifier le fichier
          </h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-4 mb-3">
            <div class="col">
              <label for="fileEditTitle" class="form-label"><span class="text-danger">*</span>Titre</label>
              <input type="text" required name="title" class="form-control" id="fileEditTitle" />
            </div>
            <div class="col">
              <label for="fileEditSlug" class="form-label">Slug (optionnel)</label>
              <input type="text" name="slug" class="form-control" id="fileEditSlug" />
            </div>
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
<script defer>
    $(document).ready(function () {
      var dataTable = $("#books").DataTable();
    });

    const fileEditModal = document.getElementById("fileEditModal");
    if (fileEditModal) {
      fileEditModal.addEventListener("show.bs.modal", function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute("data-id");
        const title = button.getAttribute("data-title");
        const slug = button.getAttribute("data-slug");
        const form = document.getElementById("fileEditForm");

        form.action = `/library/${id}`;
        document.getElementById("fileEditTitle").value = title || "";
        document.getElementById("fileEditSlug").value = slug || "";
      });
    }

    const fileUpload = document.getElementById("fileUpload");
    const fileTitle = document.getElementById("fileTitle");
    if (fileUpload && fileTitle) {
      fileUpload.addEventListener("change", () => {
        const files = fileUpload.files;
        if (files.length > 0 && !fileTitle.value) {
          const fileName = files[0].name.replace(/\\.[^/.]+$/, "");
          fileTitle.value = formatString(fileName);
        }
      });
    }

    function formatString(input) {
      let formattedString = input.replace(/[-_]/g, " ");
      formattedString = formattedString
        .split(" ")
        .map((word) => {
          return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
        })
        .join(" ");
      return formattedString;
    }
</script>
@endsection

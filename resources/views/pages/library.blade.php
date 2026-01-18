@extends('base')

@section('content')
@php
    $canManageAdmins = isset($canManageAdmins) ? $canManageAdmins : (auth()->check() && auth()->user()->email === 'cheikhtiindiaye@gmail.com');
@endphp
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
                @if ($canManageAdmins)
                  <form method="post" action="{{ url('/library/' . $file->id) }}"
                    onsubmit="return confirm('Confirmer la suppression ?')">
                    @csrf
                    @method('delete')
                    <button class="btn btn-sm btn-outline-danger" type="submit">
                      <i class="ri-delete-bin-6-line"></i>
                    </button>
                  </form>
                @endif
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
        <div class="modal-footer flex-column">
          <div id="fileUploadProgressContainer" class="w-100 mb-3 d-none">
            <label class="form-label small text-muted mb-1">Téléchargement en cours...</label>
            <div class="progress" style="height: 20px;">
              <div id="fileUploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                   role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                0%
              </div>
            </div>
          </div>
          <div class="d-flex gap-2 justify-content-end w-100">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="fileCancelBtn">
              Annuler
            </button>
            <button type="submit" class="btn btn-dark" id="fileSubmitBtn">Enregistrer</button>
          </div>
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

    // Upload avec barre de progression
    const fileForm = document.querySelector('#fileModal form');
    if (fileForm) {
      fileForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const progressContainer = document.getElementById('fileUploadProgressContainer');
        const progressBar = document.getElementById('fileUploadProgressBar');
        const submitBtn = document.getElementById('fileSubmitBtn');
        const cancelBtn = document.getElementById('fileCancelBtn');

        progressContainer.classList.remove('d-none');
        submitBtn.disabled = true;
        cancelBtn.disabled = true;

        const xhr = new XMLHttpRequest();
        xhr.open('POST', this.action, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.onprogress = function(e) {
          if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = percent + '%';
            progressBar.setAttribute('aria-valuenow', percent);
            progressBar.textContent = percent + '%';

            if (percent === 100) {
              progressBar.textContent = 'Traitement en cours...';
            }
          }
        };

        xhr.onload = function() {
          if (xhr.status === 200 || xhr.status === 302) {
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-success');
            progressBar.textContent = 'Terminé !';
            setTimeout(() => window.location.reload(), 500);
          } else {
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-danger');
            progressBar.textContent = 'Erreur !';
            submitBtn.disabled = false;
            cancelBtn.disabled = false;
            setTimeout(() => {
              progressContainer.classList.add('d-none');
              progressBar.classList.remove('bg-danger');
              progressBar.classList.add('progress-bar-animated');
              progressBar.style.width = '0%';
              progressBar.textContent = '0%';
            }, 2000);
          }
        };

        xhr.onerror = function() {
          progressBar.classList.remove('progress-bar-animated');
          progressBar.classList.add('bg-danger');
          progressBar.textContent = 'Erreur réseau !';
          submitBtn.disabled = false;
          cancelBtn.disabled = false;
        };

        xhr.send(formData);
      });
    }

    // Reset progress bar quand le modal est fermé
    const fileModal = document.getElementById('fileModal');
    if (fileModal) {
      fileModal.addEventListener('hidden.bs.modal', function() {
        const progressContainer = document.getElementById('fileUploadProgressContainer');
        const progressBar = document.getElementById('fileUploadProgressBar');
        const submitBtn = document.getElementById('fileSubmitBtn');
        const cancelBtn = document.getElementById('fileCancelBtn');

        progressContainer.classList.add('d-none');
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
        progressBar.classList.remove('bg-success', 'bg-danger');
        progressBar.classList.add('progress-bar-animated');
        submitBtn.disabled = false;
        cancelBtn.disabled = false;
      });
    }
</script>
@endsection

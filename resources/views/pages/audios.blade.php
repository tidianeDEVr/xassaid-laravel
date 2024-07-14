@extends('base')

@section('content')
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1>Fichiers Audios</h1>
      <div class="d-flex">
        <a href="/categories/audios" class="mx-3 btn btn-outline-dark">
          Catégories d'audios
        </a>
        <button
          type="button"
          class="btn btn-dark"
          data-bs-toggle="modal"
          data-bs-target="#audioModal"
        >
          Ajouter un nouveau fichier
        </button>
      </div>
    </div>

    <div
      class="modal fade"
      id="audioModal"
      tabindex="-1"
      aria-labelledby="audioModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form
            class="needs-validation"
            enctype="multipart/form-data"
            method="post"
          >
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="audioModalLabel">
                Ajouter d'un nouveau fichier
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
                  <label for="title" class="form-label"
                    ><span class="text-danger">*</span>Titre</label
                  >
                  <input
                    type="text"
                    required
                    class="form-control"
                    id="title"
                  />
                </div>
                <div class="col">
                  <label for="category" class="form-label"
                    ><span class="text-danger">*</span>Catégorie</label
                  >
                  <select
                    class="form-select"
                    aria-label="Default select example"
                  >
                    <option selected>Open this select menu</option>
                    <option value="1">One</option>
                    <option value="2">Two</option>
                    <option value="3">Three</option>
                  </select>
                </div>
              </div>
              <div class="mb-3">
                <label for="audioFile" class="form-label"
                  ><span class="text-danger">*</span>Fichier audio -
                  MP3</label
                >
                <input
                  class="form-control"
                  type="file"
                  id="audioFile"
                  accept="audio/*"
                  required
                />
              </div>
              <div class="mb-3">
                <label for="coverImage" class="form-label"
                  >Image de couverture</label
                >
                <input
                  class="form-control"
                  type="file"
                  id="coverImage"
                  accept="image/*"
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

    <table id="audios" class="table table-bordered" style="width: 100%">
      <thead>
        <tr>
          <th>ID</th>
          <th>Titre</th>
          <th>Slug</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Tiger Nixon</td>
          <td>System Architect</td>
          <td>Edinburgh</td>
          <td>
            <div class="d-flex gap-3">
              <button class="btn btn-sm btn-outline-danger">
                <i class="ri-delete-bin-6-line"></i>
              </button>
              <button class="btn btn-sm btn-outline-primary">
                <i class="ri-edit-box-line"></i>
              </button>
              <button class="btn btn-sm btn-outline-success">
                <i class="ri-eye-line"></i>
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <script>
      $(document).ready(function () {
        var dataTable = $("#audios").DataTable();
      });
      let audioFile = document.querySelector("#audioFile");
      let titleInput = document.querySelector("#title");
      audioFile.addEventListener("change", () => {
        const files = audioFile.files;
        if (files.length > 0) {
          const fileName = files[0].name;
          titleInput.value = formatString(fileName);
        }
      });
    
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

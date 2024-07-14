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
            enctype="multipart/form-data"
            method="post"
            action="{{url('/audios')}}"
          >
          @csrf
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
                    name="title"
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
                    name="category"
                  >
                    <option selected value="0">-- Veuillez choisir une catégorie --</option>
                    @foreach($categories as $category)
                    <option value="{{$category->slug}}">{{$category->type}} - {{$category->title}}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="mb-3">
                <label for="audio" class="form-label"
                  ><span class="text-danger">*</span>Fichier audio -
                  MP3</label
                >
                <input
                  class="form-control"
                  type="file"
                  id="audio"
                  name="audio"
                  accept="audio/*"
                  required
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
          <th>Catégorie</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($audios as $index=>$audio)
        <tr>
          <td>{{$index+1}}</td>
          <td>{{$audio->title}}</td>
          <td>{{$audio->slug}}</td>
          <td>{{$audio->category->type}} - {{$audio->category->title}}</td>
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
        @endforeach
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

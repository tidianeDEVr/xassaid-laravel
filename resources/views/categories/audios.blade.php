@extends('base')

@section('content')
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1>Catégories Audios</h1>
      <div class="d-flex">
        <a href="/audios" class="mx-3 btn btn-outline-dark">
          Fichiers audios
        </a>
        <button
          type="button"
          class="btn btn-dark"
          data-bs-toggle="modal"
          data-bs-target="#categoryAudioModal"
        >
          Ajouter une catégorie
        </button>
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

    <!-- Modal -->
    <div
      class="modal fade"
      id="categoryAudioModal"
      tabindex="-1"
      aria-labelledby="categoryAudioModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form method="post" enctype="multipart/form-data" action="{{url('/categories/audios')}}">
            @csrf
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="audioModalLabel">
                Ajouter une catégorie
              </h1>
              <button
                type="button"
                class="btn-close"
                data-bs-dismiss="modal"
                aria-label="Close"
              ></button>
            </div>
            <div class="modal-body">
              <div class="row row-cols-1 g-4 mb-3">
                <div class="col">
                  <label for="title" class="form-label"
                    ><span class="text-danger">*</span>Titre</label
                  >
                  <input
                    type="text"
                    name="title"
                    required
                    class="form-control"
                    id="title"
                  />
                </div>
                <div class="col">
                  <label for="category" class="form-label"
                    ><span class="text-danger">*</span>Type</label
                  >
                  <select
                    class="form-select"
                    name="type"
                    id="type"
                  >
                    <option selected value="0">-- Veuillez choisir le type --</option>
                    <option value="makk-gni">Makk gni</option>
                    <option value="kourels-yii">Kourels yii</option>
                    <option class="rajass-kat-yii">Rajass</option>
                    <option>Samm Fall</option>
                    <option>Prestations de Kourels</option>
                  </select>
                </div>
                <div class="col">
                  <label for="coverImage" class="form-label"
                    >Image de couverture</label
                  >
                  <input
                    class="form-control"
                    type="file"
                    id="coverImage"
                    name="coverImage"
                    accept="image/*"
                  />
                </div>
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

    <table id="audios-categories" class="table table-bordered" style="width: 100%">
      <thead>
        <tr>
          <th>N*</th>
          <th>Titre</th>
          <th>Slug</th>
          <th>Type</th>
          <th>Image</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($categories as $index=>$category)
        <tr>
          <td>{{$index + 1}}</td>
          <td>{{$category->title}}</td>
          <td>{{$category->slug}}</td>
          <td>{{$category->type}}</td>
          <td>
            @if($category->coverImagePath)
            <a href="https://files.xassaid.com/images/{{$category->coverImagePath}}" target="_blank">
              <img class="img-thumbnail" width="50" src="https://files.xassaid.com/images/{{$category->coverImagePath}}" alt="{{$category->title}}">
            </a>
            @else
            <i class="ri-close-circle-line"></i> Pas d'image !
            @endif
          </td>
          <td>
            <div class="d-flex gap-3">
              <button class="btn btn-sm btn-outline-danger">
                <i class="ri-delete-bin-6-line"></i>
              </button>
              <button class="btn btn-sm btn-outline-primary">
                <i class="ri-edit-box-line"></i>
              </button>
              <a href="https://xassaid.com/" target="_blank" class="btn btn-sm btn-outline-success">
                <i class="ri-eye-line"></i>
              </a>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
</div>
<script>
    $(document).ready(function () {
        var dataTable = $("#audios-categories").DataTable();
      });
</script>
@endsection
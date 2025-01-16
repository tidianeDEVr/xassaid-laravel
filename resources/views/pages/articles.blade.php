@extends('base')

@section('content')
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1>Articles</h1>
      <a href="/articles/create" class="mx-3 btn btn-outline-dark">
          Ajouter un article
        </a>
    </div>
    <table id="articles" class="table table-bordered" style="width: 100%">
      <thead>
        <tr>
          <th>N*</th>
          <th>Titre</th>
          <th>Slug</th>
          <th>Image</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($articles as $index=>$article)
        <tr>
          <td>{{$index+1}}</td>
          <td>{{$article->title}}</td>
          <td>{{$article->slug}}</td>
          <td>
          @if($article->image)
            <a href="https://files.xassaid.com/images/{{$article->image}}" target="_blank">
              <img class="img-thumbnail" width="50" src="https://files.xassaid.com/images/{{$article->image}}" alt="{{$article->title}}">
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
        var dataTable = $("#articles").DataTable();
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
</script>
@endsection
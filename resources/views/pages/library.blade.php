@extends('base')

@section('content')
<div class="container py-4">
    <h1>Bibliothèque</h1>
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
            <div class="d-flex gap-3">
              <button class="btn btn-sm btn-outline-danger">
                <i class="ri-delete-bin-6-line"></i>
              </button>
              <button class="btn btn-sm btn-outline-primary">
                <i class="ri-edit-box-line"></i>
              </button>
              <a href="https://xassaid.com/durus/{{$file->slug}}" target="_blank" class="btn btn-sm btn-outline-success">
                <i class="ri-eye-line"></i>
              </a>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
<script defer>
    $(document).ready(function () {
      var dataTable = $("#books").DataTable();
    });
</script>
@endsection

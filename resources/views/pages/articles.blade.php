@extends('base')

@section('content')
@php
    $canManageAdmins = isset($canManageAdmins) ? $canManageAdmins : (auth()->check() && auth()->user()->email === 'cheikhtiindiaye@gmail.com');
@endphp
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-3 gap-2">
      <h1>Articles</h1>
      <a href="/articles/create" class="btn btn-outline-dark">
          Ajouter un article
        </a>
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
              <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-primary" href="{{ url('/articles/' . $article->id . '/edit') }}">
                  <i class="ri-edit-box-line"></i>
                </a>
                @if ($canManageAdmins)
                  <form method="post" action="{{ url('/articles/' . $article->id) }}"
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

<script>
  $(document).ready(function () {
        var dataTable = $("#articles").DataTable();
      });
</script>
@endsection

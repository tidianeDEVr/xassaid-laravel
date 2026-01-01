@extends('base')

@section('content')
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-3 gap-2">
            <h1>Catégories Audios</h1>
            <div class="d-flex flex-column flex-md-row gap-2">
                <a href="/audios" class="btn btn-outline-dark">
                    Fichiers audios
                </a>
                <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#categoryAudioModal">
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
        <div class="modal fade" id="categoryAudioModal" tabindex="-1" aria-labelledby="categoryAudioModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="post" enctype="multipart/form-data" action="{{ url('/categories/audios') }}">
                        @csrf
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="audioModalLabel">
                                Ajouter une catégorie
                            </h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row row-cols-1 g-4 mb-3">
                                <div class="col">
                                    <label for="title" class="form-label"><span class="text-danger">*</span>Titre</label>
                                    <input type="text" name="title" required class="form-control" id="title" />
                                </div>
                                <div class="col">
                                    <label for="type" class="form-label"><span class="text-danger">*</span>Type</label>
                                    <select class="form-select" name="type" id="type" required>
                                        <option selected value="0">-- Veuillez choisir le type --</option>
                                        <option value="makk-gni">Makk gni</option>
                                        <option value="kourels-yii">Kourels yii</option>
                                        <option value="rajass-kat-yii">Rajass</option>
                                        <option value="autres">Autres</option>
                                    </select>
                                </div>
                                <div class="col">
                                    <label for="coverImage" class="form-label">Image de couverture</label>
                                    <input class="form-control" type="file" id="coverImage" name="coverImage"
                                        accept="image/*" />
                                </div>
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

        <div class="table-responsive">
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
                    @foreach ($categories as $index => $category)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $category->title }}</td>
                            <td>{{ $category->slug }}</td>
                            <td>{{ $category->type }}</td>
                            <td>
                                @if ($category->coverImagePath)
                                    <a href="https://files.xassaid.com/images/{{ $category->coverImagePath }}" target="_blank">
                                        <img class="img-thumbnail" width="50"
                                            src="https://files.xassaid.com/images/{{ $category->coverImagePath }}"
                                            alt="{{ $category->title }}">
                                    </a>
                                @else
                                    <i class="ri-close-circle-line"></i> Pas d'image !
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#categoryEditModal" data-id="{{ $category->id }}"
                                        data-title="{{ $category->title }}" data-type="{{ $category->type }}">
                                        <i class="ri-edit-box-line"></i>
                                    </button>
                                    <form method="post" action="{{ url('/categories/audios/' . $category->id) }}"
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

    <div class="modal fade" id="categoryEditModal" tabindex="-1" aria-labelledby="categoryEditModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" enctype="multipart/form-data" id="categoryEditForm">
                    @csrf
                    @method('put')
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="categoryEditModalLabel">
                            Modifier la catégorie
                        </h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row row-cols-1 g-4 mb-3">
                            <div class="col">
                                <label for="categoryEditTitle" class="form-label"><span
                                        class="text-danger">*</span>Titre</label>
                                <input type="text" name="title" required class="form-control" id="categoryEditTitle" />
                            </div>
                            <div class="col">
                                <label for="categoryEditType" class="form-label"><span class="text-danger">*</span>Type</label>
                                <select class="form-select" name="type" id="categoryEditType" required>
                                    <option value="makk-gni">Makk gni</option>
                                    <option value="kourels-yii">Kourels yii</option>
                                    <option value="rajass-kat-yii">Rajass</option>
                                    <option value="autres">Autres</option>
                                </select>
                            </div>
                            <div class="col">
                                <label for="categoryEditCover" class="form-label">Image de couverture (optionnel)</label>
                                <input class="form-control" type="file" id="categoryEditCover" name="coverImage"
                                    accept="image/*" />
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
    <script>
        $(document).ready(function() {
            var dataTable = $("#audios-categories").DataTable();
        });

        const categoryEditModal = document.getElementById("categoryEditModal");
        if (categoryEditModal) {
            categoryEditModal.addEventListener("show.bs.modal", function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute("data-id");
                const title = button.getAttribute("data-title");
                const type = button.getAttribute("data-type");
                const form = document.getElementById("categoryEditForm");

                form.action = `/categories/audios/${id}`;
                document.getElementById("categoryEditTitle").value = title || "";
                document.getElementById("categoryEditType").value = type || "";
            });
        }
    </script>
@endsection

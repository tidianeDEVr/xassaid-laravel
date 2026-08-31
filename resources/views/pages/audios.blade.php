@extends('base')

@section('content')
@php
    $canManageAdmins = isset($canManageAdmins) ? $canManageAdmins : (auth()->check() && auth()->user()->email === 'cheikhtiindiaye@gmail.com');
@endphp
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-3 gap-2">
            <h1>Fichiers Audios</h1>
            <div class="d-flex flex-column flex-md-row gap-2">
                <a href="/categories/audios" class="btn btn-outline-dark">
                    Catégories d'audios
                </a>
                <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#audioImportModal">
                    <i class="ri-download-cloud-2-line"></i> Importer depuis des liens
                </button>
                <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#audioModal">
                    Ajouter un nouveau fichier
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

        <div class="modal fade" id="audioModal" tabindex="-1" aria-labelledby="audioModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form enctype="multipart/form-data" method="post" action="{{ url('/audios') }}">
                        @csrf
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="audioModalLabel">
                                Ajouter d'un nouveau fichier
                            </h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-4 mb-3">
                                <div class="col">
                                    <label for="title" class="form-label"><span class="text-danger">*</span>Titre</label>
                                    <input type="text" required name="title" class="form-control" id="title" />
                                </div>
                                <div class="col">
                                    <label for="category" class="form-label"><span
                                            class="text-danger">*</span>Catégorie</label>
                                    <select class="form-select" name="category" id="category" required>
                                        <option selected value="">-- Veuillez choisir une catégorie --</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->slug }}">{{ $category->type }} -
                                                {{ $category->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="audio" class="form-label"><span class="text-danger">*</span>Fichier audio -
                                    MP3</label>
                                <input class="form-control" type="file" id="audio" name="audio" accept="audio/*"
                                    required />
                            </div>
                        </div>
                        <div class="modal-footer flex-column">
                            <div id="uploadErrorMessage" class="alert alert-danger w-100 mb-2 small d-none" style="white-space: pre-wrap; word-break: break-word;"></div>
                            <div id="uploadProgressContainer" class="w-100 mb-3 d-none">
                                <label class="form-label small text-muted mb-1">Téléchargement en cours...</label>
                                <div class="progress" style="height: 20px;">
                                    <div id="uploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                         role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                        0%
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 justify-content-end w-100">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cancelBtn">
                                    Annuler
                                </button>
                                <button type="submit" class="btn btn-dark" id="submitBtn">Enregistrer</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if (isset($imports) && $imports->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="ri-download-cloud-2-line"></i> Imports en cours / en échec
                    <span class="badge bg-secondary">{{ $imports->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Lien</th>
                                <th>Catégorie</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($imports as $import)
                                <tr>
                                    <td style="max-width: 340px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <a href="{{ $import->source_url }}" target="_blank" rel="noopener">
                                            {{ $import->source_url }}
                                        </a>
                                    </td>
                                    <td>{{ optional($import->category)->title ?? '—' }}</td>
                                    <td>
                                        @if ($import->status === 'failed')
                                            <span class="badge bg-danger">Échec</span>
                                            <small class="text-muted d-block">{{ $import->error }}</small>
                                        @else
                                            <span class="badge bg-info text-dark">
                                                <span class="spinner-border spinner-border-sm" role="status" style="width: 10px; height: 10px;"></span>
                                                En traitement
                                            </span>
                                            <small class="text-muted d-block">Rechargez la page pour suivre.</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            @if ($import->status === 'failed')
                                                <form method="post" action="{{ url('/audios/imports/' . $import->id . '/retry') }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-warning" type="submit">
                                                        <i class="ri-restart-line"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="post" action="{{ url('/audios/imports/' . $import->id) }}"
                                                onsubmit="return confirm('Abandonner cet import ?')">
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
        @endif

        <div class="table-responsive">
            <table id="audios" class="table table-bordered" style="width: 100%">
                <thead>
                    <tr>
                        <th>N*</th>
                        <th>Titre</th>
                        <th>Slug</th>
                        <th>Catégorie</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($audios as $index => $audio)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $audio->title }}</td>
                            <td>{{ $audio->slug }}</td>
                            <td>
                                @if ($audio->category)
                                    {{ $audio->category->type }} | {{ $audio->category->title }}
                                @else
                                    <span class="text-muted">Sans catégorie</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#audioEditModal" data-id="{{ $audio->id }}"
                                        data-title="{{ $audio->title }}"
                                        data-category="{{ optional($audio->category)->slug }}">
                                        <i class="ri-edit-box-line"></i>
                                    </button>
                                    @if ($canManageAdmins)
                                        <form method="post" action="{{ url('/audios/' . $audio->id) }}"
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

    <!-- Import d'audios par liens (YouTube, Instagram...) : la piste audio
         est convertie en MP3 avant l'upload. -->
    <div class="modal fade" id="audioImportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" action="{{ url('/audios/import') }}">
                    @csrf
                    <div class="modal-header">
                        <h1 class="modal-title fs-5">Importer des audios depuis des liens</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="audioImportCategory" class="form-label"><span
                                    class="text-danger">*</span>Catégorie</label>
                            <select class="form-select" name="category_id" id="audioImportCategory" required>
                                <option selected value="">-- Veuillez choisir une catégorie --</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->type }} -
                                        {{ $category->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-1">
                            <label class="form-label"><span class="text-danger">*</span>Liens (un par ligne, 20 max)</label>
                            <textarea name="links" class="form-control" rows="6" required
                                placeholder="https://www.youtube.com/watch?v=…&#10;https://www.instagram.com/reel/…"></textarea>
                        </div>
                        <small class="text-muted">
                            La piste audio de chaque vidéo est téléchargée (yt-dlp), convertie en
                            MP3 ({{ (int) config('services.xassaid.audio_bitrate', 96) }} kbps)
                            puis uploadée. Le titre reprend celui de la vidéo. Le traitement tourne
                            en arrière-plan : les imports apparaissent en haut de cette page.
                        </small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-dark">Lancer l'import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="audioEditModal" tabindex="-1" aria-labelledby="audioEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form enctype="multipart/form-data" method="post" id="audioEditForm">
                    @csrf
                    @method('put')
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="audioEditModalLabel">
                            Modifier le fichier
                        </h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-4 mb-3">
                            <div class="col">
                                <label for="audioEditTitle" class="form-label"><span
                                        class="text-danger">*</span>Titre</label>
                                <input type="text" required name="title" class="form-control" id="audioEditTitle" />
                            </div>
                            <div class="col">
                                <label for="audioEditCategory" class="form-label"><span
                                        class="text-danger">*</span>Catégorie</label>
                                <select class="form-select" name="category" id="audioEditCategory" required>
                                    <option value="">-- Veuillez choisir une catégorie --</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->slug }}">{{ $category->type }} -
                                            {{ $category->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="audioEditFile" class="form-label">Nouveau fichier audio (optionnel)</label>
                            <input class="form-control" type="file" id="audioEditFile" name="audio" accept="audio/*" />
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
            var dataTable = $("#audios").DataTable();

            // Select de catégorie avec recherche (équivalent p-select filter de PrimeNG)
            $('#category').select2({
                theme: 'bootstrap-5',
                language: 'fr',
                placeholder: '-- Veuillez choisir une catégorie --',
                width: '100%',
                dropdownParent: $('#audioModal')
            });
            $('#audioEditCategory').select2({
                theme: 'bootstrap-5',
                language: 'fr',
                placeholder: '-- Veuillez choisir une catégorie --',
                width: '100%',
                dropdownParent: $('#audioEditModal')
            });
            $('#audioImportCategory').select2({
                theme: 'bootstrap-5',
                language: 'fr',
                placeholder: '-- Veuillez choisir une catégorie --',
                width: '100%',
                dropdownParent: $('#audioImportModal')
            });
        });
        let audioFile = document.querySelector("#audio");
        let titleInput = document.querySelector("#title");
        if (audioFile && titleInput) {
            audioFile.addEventListener("change", () => {
                const files = audioFile.files;
                if (files.length > 0) {
                    const fileName = files[0].name.replace(/\.[^/.]+$/, "");
                    titleInput.value = formatString(fileName);
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

        const audioEditModal = document.getElementById("audioEditModal");
        if (audioEditModal) {
            audioEditModal.addEventListener("show.bs.modal", function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute("data-id");
                const title = button.getAttribute("data-title");
                const category = button.getAttribute("data-category");
                const form = document.getElementById("audioEditForm");

                form.action = `/audios/${id}`;
                document.getElementById("audioEditTitle").value = title || "";
                $("#audioEditCategory").val(category || "").trigger("change");
            });
        }

        // Upload avec barre de progression
        const audioForm = document.querySelector('#audioModal form');
        if (audioForm) {
            audioForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const progressContainer = document.getElementById('uploadProgressContainer');
                const progressBar = document.getElementById('uploadProgressBar');
                const submitBtn = document.getElementById('submitBtn');
                const cancelBtn = document.getElementById('cancelBtn');
                const errorBox = document.getElementById('uploadErrorMessage');

                errorBox.classList.add('d-none');
                errorBox.textContent = '';
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
                        let message = 'Erreur inattendue (HTTP ' + xhr.status + ')';
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (res.error) {
                                message = res.error;
                            } else if (res.errors) {
                                // Erreurs de validation Laravel : { errors: { champ: [messages] } }
                                message = Object.values(res.errors).flat().join('\n');
                            } else if (res.message) {
                                message = res.message;
                            }
                        } catch (e) {
                            if (xhr.responseText) {
                                message += ' : ' + xhr.responseText.substring(0, 300);
                            }
                        }

                        progressBar.classList.remove('progress-bar-animated');
                        progressBar.classList.add('bg-danger');
                        progressBar.textContent = 'Erreur (HTTP ' + xhr.status + ')';
                        errorBox.textContent = message;
                        errorBox.classList.remove('d-none');
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
                    errorBox.textContent = 'Erreur réseau : impossible de joindre le serveur (connexion interrompue ou serveur injoignable).';
                    errorBox.classList.remove('d-none');
                    submitBtn.disabled = false;
                    cancelBtn.disabled = false;
                };

                xhr.send(formData);
            });
        }

        // Reset progress bar quand le modal est fermé
        const audioModal = document.getElementById('audioModal');
        if (audioModal) {
            audioModal.addEventListener('hidden.bs.modal', function() {
                const progressContainer = document.getElementById('uploadProgressContainer');
                const progressBar = document.getElementById('uploadProgressBar');
                const submitBtn = document.getElementById('submitBtn');
                const cancelBtn = document.getElementById('cancelBtn');

                progressContainer.classList.add('d-none');
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                progressBar.classList.remove('bg-success', 'bg-danger');
                progressBar.classList.add('progress-bar-animated');
                submitBtn.disabled = false;
                cancelBtn.disabled = false;

                const errorBox = document.getElementById('uploadErrorMessage');
                errorBox.classList.add('d-none');
                errorBox.textContent = '';
            });
        }
    </script>
@endsection

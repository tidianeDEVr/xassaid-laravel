@extends('base')

@section('content')
<div class="container py-4">
    <h1>Tableau de bord</h1>
    <div class="row">
        <div class="col">
        <div class="card">
            <div class="card-body">
            <div class="d-flex">
                <div
                class="bg-dark text-white d-flex align-items-center justify-content-center fs-2"
                style="width: 50px; height: 50px; border-radius: 3px"
                >
                <i class="ri-folder-music-line"></i>
                </div>
                <div
                class="px-3 fs-5 d-flex align-items-center justify-content-center"
                >
                <span>{{$overview['audiosCounts']}} Fichiers Audios</span>
                </div>
            </div>
            </div>
        </div>
        </div>
        <div class="col">
        <div class="card">
            <div class="card-body">
            <div class="d-flex">
                <div
                class="bg-dark text-white d-flex align-items-center justify-content-center fs-2"
                style="width: 50px; height: 50px; border-radius: 3px"
                >
                <i class="ri-file-pdf-2-line"></i>
                </div>
                <div
                class="px-3 fs-5 d-flex align-items-center justify-content-center"
                >
                <span>0 Fichiers PDFs</span>
                </div>
            </div>
            </div>
        </div>
        </div>
        <div class="col">
        <div class="card">
            <div class="card-body">
            <div class="d-flex">
                <div
                class="bg-dark text-white d-flex align-items-center justify-content-center fs-2"
                style="width: 50px; height: 50px; border-radius: 3px"
                >
                <i class="ri-video-line"></i>
                </div>
                <div
                class="px-3 fs-5 d-flex align-items-center justify-content-center"
                >
                <span>{{$overview['categoriesCounts']}} Catégories</span>
                </div>
            </div>
            </div>
        </div>
        </div>
        <div class="col">
        <div class="card">
            <div class="card-body">
            <div class="d-flex">
                <div
                class="bg-dark text-white d-flex align-items-center justify-content-center fs-2"
                style="width: 50px; height: 50px; border-radius: 3px"
                >
                <i class="ri-group-line"></i>
                </div>
                <div
                class="px-3 fs-5 d-flex align-items-center justify-content-center"
                >
                <span>{{$overview['usersCounts']}} Utilisateurs</span>
                </div>
            </div>
            </div>
        </div>
        </div>
    </div>
</div>
@endsection
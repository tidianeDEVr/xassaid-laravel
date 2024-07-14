<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8" />
        <title>Xassaid | Tableau de bord</title>
        <link rel="icon" type="image/x-icon" href="{{asset('favicon.png')}}" />
        <meta name="robots" content="noindex" />
        <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC"
        crossorigin="anonymous"
        />
        <link
        href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css"
        rel="stylesheet"
        />
        <link
        href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css"
        rel="stylesheet"
        />
        <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.min.css"
        />
        <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
        <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"
        ></script>
        <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
    </head>
    <body class="bg-light">
        <div class="d-flex align-items-center justify-content-center vh-100">
        <div class="card" style="width: 22rem">
            <div class="card-body">
            <div class="d-flex justify-content-center">
                <img src="{{asset('logo-xassaid.png')}}" width="180" />
            </div>
            <form method="POST" action="{{url('/login')}}">
                @csrf
                <div class="mb-3">
                <label for="exampleFormControlInput1" class="form-label"
                    >Adresse e-mail</label
                >
                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    placeholder="nom@email.com"
                    autocomplete="email"
                />
                <span class="text-danger">
                </span>
                </div>
                <div class="mb-3">
                <label for="exampleFormControlInput1" class="form-label"
                    >Mot de passe</label
                >
                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    required
                    placeholder="*************"
                    autocomplete="current-password"
                />
                <span class="text-danger">
                @error('error')
                    {{$message}}
                @enderror
                </span>
                </div>
                <div class="d-flex align-items-center justify-content-center">
                <button class="mt-3 btn btn-success" type="submit">
                    Soumettre
                </button>
                </div>
            </form>
            </div>
        </div>
        </div>
    </body>
</html>

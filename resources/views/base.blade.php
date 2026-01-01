<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Xassaid | Tableau de bord</title>
    <link rel="icon" type="image/x-icon" href="{{asset('favicon.png')}}" />
    <meta name="robots" content="noindex" />
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
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
    @yield('styles')
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
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container">
            <a class="navbar-brand" href="/">
            <img src="{{asset('xassaid_dark.png')}}" alt="xassaid logo" width="130"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
                <a class="nav-link" href="/">Tableau de bord</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/audios">Audios</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/articles">Articles</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/library">Bibliothèque</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/users">Utilisateurs</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/analytics">Analytics</a>
            </li>
            @auth
            <li class="nav-item">
                <form
                action="{{route('security.logout')}}"
                method="POST"
                class="m-0 p-0"
                >
                @method('delete')
                @csrf
                <button type="submit" class="btn nav-link">Déconnexion</button>
                </form>
            </li>    
            @endauth
            </ul>
            </div>
        </div>
    </nav>
    @yield('content')
    <footer class="position-fixed bottom-0 vw-100 opacity-50">
        <p class="text-center text-muted">
            Djeureudjeuf Cheikh Ahmadou Bamba © Xassaid
        </p>
    </footer>
    @yield('scripts')
  </body>

  </html>

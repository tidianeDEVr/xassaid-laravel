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
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container">
            <a class="navbar-brand" href="/">Xassaid</a>
            <ul class="navbar-nav mr-auto">
            <li class="nav-item">
                <a class="nav-link" href="/">Tableau de bord</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/audios">Audios</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/library">Bibliothèque</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/users">Utilisateurs</a>
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
    </nav>
    @yield('content')
    <footer class="position-fixed bottom-0 vw-100 opacity-50">
        <p class="text-center text-muted">
            Djeureudjeuf Cheikh Ahmadou Bamba © Xassaid
        </p>
    </footer>
  </body>

  </html>
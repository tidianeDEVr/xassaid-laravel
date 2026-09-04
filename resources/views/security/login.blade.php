<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Xassaid | Connexion</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.png') }}" />
    <meta name="robots" content="noindex" />
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.min.css" />
    <link rel="stylesheet" href="{{ asset('backoffice/app.css') }}?v={{ filemtime(public_path('backoffice/app.css')) }}">
</head>
<body>
    <div class="login">
        <div class="card">
            <div class="brand"><img src="{{ asset('logo-xassaid.png') }}" alt="Xassaid" /></div>
            <form method="POST" action="/login">
                @csrf
                @error('error')
                    <div class="flash err" role="alert"><i class="ri-error-warning-line"></i><div>{{ $message }}</div></div>
                @enderror
                <div class="field">
                    <label for="email">Adresse e-mail</label>
                    <input type="email" class="input" id="email" name="email" value="{{ old('email') }}" required placeholder="nom@email.com" autocomplete="email" autofocus />
                </div>
                <div class="field">
                    <label for="password">Mot de passe</label>
                    <input type="password" class="input" id="password" name="password" required autocomplete="current-password" />
                </div>
                <button class="btn btn-primary" type="submit">Se connecter</button>
            </form>
        </div>
    </div>
</body>
</html>

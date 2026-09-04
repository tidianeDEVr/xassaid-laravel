<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Xassaid | @yield('title', 'Backoffice')</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.png') }}" />
    <meta name="robots" content="noindex" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.min.css" />
    <link rel="stylesheet" href="{{ asset('backoffice/app.css') }}?v={{ filemtime(public_path('backoffice/app.css')) }}">
    @yield('styles')
</head>

<body>
    @php
        $user = auth()->user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        // Menu : groupe => [libellé, chemin, icône, motif « actif »]
        $menu = [
            'Général' => [
                ['Tableau de bord', '/', 'ri-dashboard-line', '/'],
            ],
            'Contenu' => [
                ['Audios', '/audios', 'ri-folder-music-line', 'audios*'],
                ['Catégories d\'audios', '/categories/audios', 'ri-price-tag-3-line', 'categories/*'],
                ['Articles', '/articles', 'ri-news-line', 'articles*'],
                ['Bibliothèque', '/library', 'ri-file-pdf-2-line', 'library*'],
            ],
        ];
        if ($isSuperAdmin) {
            $menu['Communauté'] = [
                ['Vidéos', '/videos', 'ri-video-line', 'videos*'],
                ['Comptes app', '/app-users', 'ri-user-3-line', 'app-users*'],
            ];
            if (config('services.youtube_automation.url')) {
                $menu['Publication'] = [['YouTube', '/youtube', 'ri-youtube-line', 'youtube*']];
            }
            $menu['Administration'] = [
                ['Utilisateurs', '/users', 'ri-shield-user-line', 'users*'],
                ['Santé des fichiers', '/file-health', 'ri-heart-pulse-line', 'file-health*'],
            ];
        }
        $isActive = fn (string $pattern) => $pattern === '/' ? request()->is('/') : request()->is($pattern);
    @endphp

    <div class="shell">
        @auth
            <aside class="sidebar" id="sidebar">
                <div class="brand">
                    <a href="/"><img src="{{ asset('logo-xassaid.png') }}" alt="Xassaid"></a>
                    <span class="brand-tag">backoffice</span>
                </div>
                @include('partials.sidebar-nav', ['menu' => $menu, 'isActive' => $isActive])
                <div class="user-box">
                    <div class="email">{{ $user->email }}</div>
                    <div class="role">{{ $isSuperAdmin ? 'Super administrateur' : 'Administrateur' }}</div>
                    <form action="{{ route('security.logout') }}" method="POST">
                        @method('delete')
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm"><i class="ri-logout-box-r-line"></i> Se déconnecter</button>
                    </form>
                </div>
            </aside>
            <div class="drawer-backdrop"></div>
        @endauth

        <div class="main">
            @auth
                <div class="topbar">
                    <a href="/"><img src="{{ asset('logo-xassaid.png') }}" alt="Xassaid"></a>
                    <button class="btn btn-sm" type="button" data-drawer-toggle aria-controls="sidebar"><i class="ri-menu-line"></i> Menu</button>
                </div>
            @endauth

            <main class="content">
                @yield('content')
            </main>
            <footer class="footer">Djeureudjeuf Cheikh Ahmadou Bamba © Xassaid</footer>
        </div>
    </div>
    <script src="{{ asset('backoffice/app.js') }}?v={{ filemtime(public_path('backoffice/app.js')) }}"></script>
    @yield('scripts')
</body>

</html>

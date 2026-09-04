<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production') || config('app.force_https')) {
            URL::forceScheme('https');
        }

        Route::middleware('api')
            ->prefix('api/v1')
            ->group(base_path('routes/api.php'));

        // Déclaré à la résolution du RateLimiter (première requête throttlée),
        // pas au boot : passer par la façade ici ouvrirait le magasin de cache
        // — donc une connexion base — à chaque requête et chaque commande
        // artisan, y compris celles qui ne touchent jamais aux quotas.
        $this->app->resolving(
            RateLimiter::class,
            fn (RateLimiter $limiter) => $this->configureRateLimiters($limiter),
        );
    }

    /**
     * Quotas de l'API mobile (v2).
     *
     * Chaque limiteur est nommé et posé sur les routes concernées
     * (`throttle:app-register`, ...). Les actions authentifiées comptent par
     * compte — l'IP seule ne protège rien derrière un NAT opérateur ; les
     * actions publiques comptent par IP, faute de mieux.
     */
    private function configureRateLimiters(RateLimiter $limiter): void
    {
        // Création de compte : le coût principal est le spam de pseudos.
        $limiter->for('app-register', fn (Request $request) => [
            self::limit(Limit::perMinute(3)->by('reg-m:'.$request->ip())),
            self::limit(Limit::perHour(5)->by('reg-h:'.$request->ip())),
            self::limit(Limit::perDay(10)->by('reg-d:'.$request->ip())),
        ]);

        // Connexion : bloque le bourrage de mots de passe sur un pseudo donné
        // sans verrouiller tout un réseau partagé.
        $limiter->for('app-login', fn (Request $request) => [
            self::limit(Limit::perMinute(5)->by(
                'log-u:'.$request->ip().'|'.strtolower(trim((string) $request->input('username'))),
            )),
            self::limit(Limit::perMinute(20)->by('log-i:'.$request->ip())),
        ]);

        // Publication : transcodage = CPU, c'est la ressource la plus chère.
        $limiter->for('app-video', fn (Request $request) => [
            self::limit(Limit::perHour(5)->by('vid-h:'.self::actor($request))),
            self::limit(Limit::perDay(20)->by('vid-d:'.self::actor($request))),
        ]);

        $limiter->for('app-comment', fn (Request $request) => [
            self::limit(Limit::perMinute(8)->by('com-m:'.self::actor($request))),
            self::limit(Limit::perHour(60)->by('com-h:'.self::actor($request))),
        ]);

        // Likes / abonnements : généreux (l'utilisateur peut enchaîner), mais
        // borné pour empêcher le gonflage automatisé des compteurs.
        $limiter->for('app-interaction', fn (Request $request) => [
            self::limit(Limit::perMinute(60)->by('int-m:'.self::actor($request))),
            self::limit(Limit::perHour(600)->by('int-h:'.self::actor($request))),
        ]);

        $limiter->for('app-avatar', fn (Request $request) => [
            self::limit(Limit::perHour(6)->by('ava:'.self::actor($request))),
        ]);

        // Comptage de vues : public, donc par IP.
        $limiter->for('app-view', fn (Request $request) => [
            self::limit(Limit::perMinute(60)->by('view:'.$request->ip())),
        ]);

        // Lectures publiques (feed, commentaires, profils) : plafond large,
        // uniquement là pour absorber un client qui boucle.
        $limiter->for('app-read', fn (Request $request) => [
            self::limit(Limit::perMinute(120)->by('read:'.self::actor($request))),
        ]);
    }

    /** Compte connecté si le token est valide, sinon l'IP. */
    private static function actor(Request $request): string
    {
        return ($user = $request->user()) ? 'u'.$user->id : 'ip'.$request->ip();
    }

    /** Réponse 429 en JSON, message lisible côté application. */
    private static function limit(Limit $limit): Limit
    {
        return $limit->response(fn (Request $request, array $headers) => response()->json([
            'message' => 'Trop de requêtes. Réessayez dans un instant.',
        ], 429, $headers));
    }
}

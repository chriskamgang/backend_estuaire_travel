<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // Récupérer la langue depuis la session ou utiliser 'fr' par défaut
        $locale = Session::get('locale', config('app.locale', 'fr'));

        // Si un paramètre 'lang' est présent dans l'URL, changer la langue
        if ($request->has('lang') && in_array($request->lang, ['fr', 'en'])) {
            $locale = $request->lang;
            Session::put('locale', $locale);
        }

        App::setLocale($locale);

        return $next($request);
    }
}

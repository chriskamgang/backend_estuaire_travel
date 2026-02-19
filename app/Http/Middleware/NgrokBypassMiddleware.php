<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NgrokBypassMiddleware
{
    /**
     * Ajouter le header ngrok-skip-browser-warning à toutes les réponses
     * pour bypasser la page d'avertissement ngrok en développement local.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('ngrok-skip-browser-warning', 'true');
        return $response;
    }
}

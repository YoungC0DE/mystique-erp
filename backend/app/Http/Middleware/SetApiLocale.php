<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que respostas da API (validação, auth, paginação) usem português (pt_BR).
 */
class SetApiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('pt_BR');

        return $next($request);
    }
}

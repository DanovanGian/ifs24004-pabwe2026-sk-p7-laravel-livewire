<?php

namespace App\Http\Middleware;

use App\Features\Stock\Models\Stock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStockOwnership
{
    public function handle(
        Request $request,
        Closure $next,
        string $parameter = 'stock',
    ): Response {
        $stock = $request->route($parameter);
        $user = $request->user();

        abort_unless(
            $stock instanceof Stock
                && $user
                && (int) $stock->user_id === (int) $user->id,
            404,
        );

        return $next($request);
    }
}

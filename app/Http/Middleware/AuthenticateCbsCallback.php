<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCbsCallback
{
    /**
     * Handle an incoming request from CBS system.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = config('bkash.cbs_callback_api_key', 'cbs-secret-callback-key-2026');
        $providedKey   = $request->header('X-CBS-API-Key');

        if (empty($providedKey) || !hash_equals((string) $configuredKey, (string) $providedKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid or missing CBS API Key',
            ], 401);
        }

        return $next($request);
    }
}

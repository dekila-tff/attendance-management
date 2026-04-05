<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceCanonicalHost
{
    /**
     * Redirect requests to a single canonical host to prevent CSRF/session cookie mismatches.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            $appUrl = config('app.url');

            if (is_string($appUrl) && $appUrl !== '') {
                $appHost = parse_url($appUrl, PHP_URL_HOST);
                $appPort = parse_url($appUrl, PHP_URL_PORT);
                $requestHost = $request->getHost();
                $requestPort = $request->getPort();

                if ($appHost && strcasecmp($requestHost, (string) $appHost) !== 0) {
                    $portSegment = $appPort ? ':' . $appPort : '';
                    $target = $request->getScheme() . '://' . $appHost . $portSegment . $request->getRequestUri();

                    return redirect()->to($target, 302);
                }

                if ($appPort && (int) $requestPort !== (int) $appPort) {
                    $target = $request->getScheme() . '://' . $requestHost . ':' . $appPort . $request->getRequestUri();

                    return redirect()->to($target, 302);
                }
            }
        }

        return $next($request);
    }
}

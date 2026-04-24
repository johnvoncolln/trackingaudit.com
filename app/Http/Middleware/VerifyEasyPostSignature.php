<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyEasyPostSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('tracking.easypost.webhook_secret');
        $signature = $request->header('X-Hmac-Signature');

        if (! $secret || ! is_string($signature)) {
            abort(401, 'Invalid EasyPost webhook signature.');
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $this->stripPrefix($signature))) {
            abort(401, 'Invalid EasyPost webhook signature.');
        }

        return $next($request);
    }

    protected function stripPrefix(string $signature): string
    {
        foreach (['sha256=', 'hmac-sha256='] as $prefix) {
            if (str_starts_with($signature, $prefix)) {
                return substr($signature, strlen($prefix));
            }
        }

        return $signature;
    }
}

<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * FIX Railway : faire confiance à tous les proxies
     * Railway utilise un reverse proxy qui transmet les headers X-Forwarded-*
     * Sans ce fix, Laravel génère des URLs en http:// au lieu de https://
     * ce qui cause des redirections 307 et des erreurs "Mixed content"
     */
    protected $proxies = '*';

    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}

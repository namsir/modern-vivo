<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CaddyController extends Controller
{
    public function __invoke(Request $request)
    {
        $authorizedDomains = [
            'modern-vivo.test', // Add your domain here
            'www.modern-vivo.test',
        ];

        if (in_array($request->getHost(), $authorizedDomains)) {
            return response('OK', 200);
        }

        return response('Unauthorized', 401);
    }
}

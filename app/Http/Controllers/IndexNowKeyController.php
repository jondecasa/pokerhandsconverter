<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Serves the IndexNow ownership file at /<key>.txt: search engines fetch it to
 * confirm the submitter controls the site. The key isn't a secret.
 */
class IndexNowKeyController extends Controller
{
    public function __invoke(string $key): Response
    {
        $expected = (string) config('pokerhandsconverter.indexnow.key');

        abort_if($expected === '' || ! hash_equals($expected, $key), 404);

        return response($expected, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}

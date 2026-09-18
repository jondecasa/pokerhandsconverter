<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class JsonLd
{
    /**
     * A schema.org JSON-LD script tag. The context key is added here on
     * purpose: written inside a Blade view, a literal "@context" is read by
     * Blade as its own directive and replaced with PHP code, silently
     * corrupting the JSON. Tag characters are hex-escaped so a closing
     * script tag inside any value can't end the block early.
     *
     * @param  array<string, mixed>  $data
     */
    public static function script(array $data): HtmlString
    {
        $json = json_encode(
            ['@context' => 'https://schema.org'] + $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR,
        );

        return new HtmlString('<script type="application/ld+json">'.$json.'</script>');
    }
}

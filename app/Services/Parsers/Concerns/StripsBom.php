<?php

namespace App\Services\Parsers\Concerns;

trait StripsBom
{
    private function stripBom(string $content): string
    {
        $bom = "\xEF\xBB\xBF";

        return str_starts_with($content, $bom) ? substr($content, strlen($bom)) : $content;
    }
}

<?php

namespace App\Services\Parsers;

use App\DTO\TransactionData;

interface FileParserInterface
{
    /**
     * @throws \App\Exceptions\FileParseException
     */
    public function parse(string $content): array;
}

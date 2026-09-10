<?php

namespace App\Services\Parsers;

use App\Exceptions\UnsupportedFileFormatException;

class FileParserFactory
{
    /**
     * @throws UnsupportedFileFormatException
     */
    public function make(string $extension): FileParserInterface
    {
        return match (strtolower(trim($extension, '.'))) {
            'csv' => new CsvParser(),
            'json' => new JsonParser(),
            'xml' => new XmlParser(),
            default => throw new UnsupportedFileFormatException($extension),
        };
    }
}

<?php

namespace App\Exceptions;

class UnsupportedFileFormatException extends FileImportException
{
    public function __construct(string $extension)
    {
        parent::__construct("Unsupported file format: .{$extension}. Allowed formats are: CSV, JSON, XML.");
    }
}

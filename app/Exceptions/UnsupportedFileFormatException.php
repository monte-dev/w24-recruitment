<?php

namespace App\Exceptions;

use Exception;

class UnsupportedFileFormatException extends Exception
{
    public function __construct(string $extension)
    {
        parent::__construct("Unsupported file format: .{$extension}. Allowed formats are: CSV, JSON, XML.");
    }
}

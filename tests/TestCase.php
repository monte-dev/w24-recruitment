<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    protected function sampleContent(string $fileName): string
    {
        return file_get_contents(base_path("samples/{$fileName}"));
    }

    protected function sampleUploadedFile(string $fileName): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $fileName,
            $this->sampleContent($fileName)
        );
    }
}

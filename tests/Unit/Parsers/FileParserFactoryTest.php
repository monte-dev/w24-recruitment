<?php

namespace Tests\Unit\Parsers;

use App\Exceptions\UnsupportedFileFormatException;
use App\Services\Parsers\CsvParser;
use App\Services\Parsers\FileParserFactory;
use App\Services\Parsers\JsonParser;
use App\Services\Parsers\XmlParser;
use PHPUnit\Framework\TestCase;

class FileParserFactoryTest extends TestCase
{
    private FileParserFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new FileParserFactory();
    }

    public function test_it_creates_csv_parser(): void
    {
        $this->assertInstanceOf(CsvParser::class, $this->factory->make('csv'));
        $this->assertInstanceOf(CsvParser::class, $this->factory->make('.csv'));
        $this->assertInstanceOf(CsvParser::class, $this->factory->make('CSV'));
    }

    public function test_it_creates_json_parser(): void
    {
        $this->assertInstanceOf(JsonParser::class, $this->factory->make('json'));
        $this->assertInstanceOf(JsonParser::class, $this->factory->make('.JSON'));
    }

    public function test_it_creates_xml_parser(): void
    {
        $this->assertInstanceOf(XmlParser::class, $this->factory->make('xml'));
        $this->assertInstanceOf(XmlParser::class, $this->factory->make('.XML'));
    }

    public function test_it_throws_for_unsupported_format(): void
    {
        $this->expectException(UnsupportedFileFormatException::class);
        $this->factory->make('pdf');
    }
}

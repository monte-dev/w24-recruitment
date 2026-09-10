<?php

namespace Tests\Unit\Parsers;

use App\Exceptions\FileParseException;
use App\Services\Parsers\JsonParser;
use PHPUnit\Framework\TestCase;

class JsonParserTest extends TestCase
{
    private JsonParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new JsonParser();
    }

    public function test_it_parses_plain_json_array(): void
    {
        $json = json_encode([
            [
                'transaction_id' => 'TRX100',
                'account_number' => 'PL12345678901234567890123456',
                'transaction_date' => '2026-03-01',
                'amount' => 120.55,
                'currency' => 'EUR',
            ],
        ]);

        $records = $this->parser->parse($json);

        $this->assertCount(1, $records);
        $this->assertSame('TRX100', $records[0]->transactionId);
        $this->assertSame('PL12345678901234567890123456', $records[0]->accountNumber);
        $this->assertSame(120.55, $records[0]->amount);
        $this->assertSame('EUR', $records[0]->currency);
    }

    public function test_it_returns_empty_array_for_empty_string(): void
    {
        $this->assertSame([], $this->parser->parse(''));
        $this->assertSame([], $this->parser->parse('   '));
    }

    public function test_it_returns_empty_array_for_empty_list(): void
    {
        $this->assertSame([], $this->parser->parse('[]'));
    }

    public function test_it_throws_exception_on_invalid_json(): void
    {
        $this->expectException(FileParseException::class);
        $this->expectExceptionMessage('Invalid JSON file format:');
        $this->parser->parse('{invalid-json}');
    }

    public function test_it_throws_when_json_root_is_not_a_list(): void
    {
        $this->expectException(FileParseException::class);
        $this->expectExceptionMessage('JSON file must contain a list of transactions.');

        $this->parser->parse(json_encode('just a string'));
    }

    public function test_it_throws_when_json_root_is_object(): void
    {
        $this->expectException(FileParseException::class);
        $this->expectExceptionMessage('JSON file must contain a list of transactions.');

        $this->parser->parse(json_encode(['transactions' => []]));
    }

    public function test_it_parses_sample_json_structure(): void
    {
        $content = json_encode([
            [
                'transaction_id' => '550e8400-e29b-41d4-a716-446655440000',
                'account_number' => 'PL12345678901234567890123456',
                'transaction_date' => '2025-10-14',
                'amount' => 150000,
                'currency' => 'PLN',
            ],
            [
                'transaction_id' => '550e8400-e29b-41d4-a716-446655440001',
                'account_number' => 'PL98765432109876543210987654',
                'transaction_date' => '2025-10-13',
                'amount' => 20050,
                'currency' => 'USD',
            ],
        ]);

        $records = $this->parser->parse($content);

        $this->assertCount(2, $records);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $records[0]->transactionId);
        $this->assertSame('PL12345678901234567890123456', $records[0]->accountNumber);
        $this->assertSame('2025-10-14', $records[0]->transactionDate);
        $this->assertSame(150000, $records[0]->amount);
        $this->assertSame('PLN', $records[0]->currency);
    }

    public function test_it_handles_utf8_bom(): void
    {
        $bom = "\xEF\xBB\xBF";
        $json = $bom . json_encode([
            [
                'transaction_id' => 'TRX102',
                'account_number' => 'PL12345678901234567890123456',
                'transaction_date' => '2026-03-03',
                'amount' => 500,
                'currency' => 'PLN',
            ],
        ]);

        $records = $this->parser->parse($json);

        $this->assertCount(1, $records);
        $this->assertSame('TRX102', $records[0]->transactionId);
    }

    public function test_it_handles_non_object_item_in_array_as_empty_dto(): void
    {
        $records = $this->parser->parse(json_encode(['invalid_string_instead_of_object']));

        $this->assertCount(1, $records);
        $this->assertNull($records[0]->transactionId);
        $this->assertNull($records[0]->accountNumber);
        $this->assertNull($records[0]->amount);
    }

    public function test_it_keeps_valid_items_when_array_contains_mixed_types(): void
    {
        $json = json_encode([
            [
                'transaction_id' => 'TRX105',
                'account_number' => 'PL12345678901234567890123456',
                'amount' => 100,
                'currency' => 'PLN',
            ],
            'not_an_object',
            [
                'transaction_id' => 'TRX106',
                'account_number' => 'PL98765432109876543210987654',
                'amount' => 200,
                'currency' => 'EUR',
            ],
        ]);

        $records = $this->parser->parse($json);

        $this->assertCount(3, $records);
        $this->assertSame('TRX105', $records[0]->transactionId);
        $this->assertNull($records[1]->transactionId);
        $this->assertNull($records[1]->accountNumber);
        $this->assertSame('TRX106', $records[2]->transactionId);
    }
}
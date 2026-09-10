<?php

namespace Tests\Unit\Parsers;

use App\Exceptions\FileParseException;
use App\Services\Parsers\XmlParser;
use PHPUnit\Framework\TestCase;

class XmlParserTest extends TestCase
{
    private XmlParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new XmlParser();
    }

    public function test_it_parses_xml_with_transactions(): void
    {
        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <transactions>
                <transaction>
                    <transaction_id>TRX200</transaction_id>
                    <account_number>PL12345678901234567890123456</account_number>
                    <transaction_date>2026-04-01</transaction_date>
                    <amount>450.00</amount>
                    <currency>USD</currency>
                </transaction>
            </transactions>
            XML;

        $records = $this->parser->parse($xml);

        $this->assertCount(1, $records);
        $this->assertSame('TRX200', $records[0]->transactionId);
        $this->assertSame('PL12345678901234567890123456', $records[0]->accountNumber);
        $this->assertSame('450.00', $records[0]->amount);
        $this->assertSame('USD', $records[0]->currency);
    }

    public function test_it_parses_multiple_transactions(): void
    {
        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <transactions>
                <transaction>
                    <transaction_id>TRX200</transaction_id>
                    <account_number>PL12345678901234567890123456</account_number>
                    <amount>450.00</amount>
                    <currency>USD</currency>
                </transaction>
                <transaction>
                    <transaction_id>TRX201</transaction_id>
                    <account_number>PL98765432109876543210987654</account_number>
                    <amount>99.99</amount>
                    <currency>PLN</currency>
                </transaction>
            </transactions>
            XML;

        $records = $this->parser->parse($xml);

        $this->assertCount(2, $records);
        $this->assertSame('TRX200', $records[0]->transactionId);
        $this->assertSame('TRX201', $records[1]->transactionId);
    }

    public function test_it_returns_empty_array_for_empty_string(): void
    {
        $this->assertSame([], $this->parser->parse(''));
        $this->assertSame([], $this->parser->parse("  \n "));
    }

    public function test_it_handles_utf8_bom(): void
    {
        $bom = "\xEF\xBB\xBF";
        $xml = $bom . <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <transactions>
                <transaction>
                    <transaction_id>TRX202</transaction_id>
                    <account_number>PL12345678901234567890123456</account_number>
                    <amount>100.00</amount>
                    <currency>PLN</currency>
                </transaction>
            </transactions>
            XML;

        $records = $this->parser->parse($xml);

        $this->assertCount(1, $records);
        $this->assertSame('TRX202', $records[0]->transactionId);
    }

    public function test_it_throws_exception_on_malformed_xml(): void
    {
        $this->expectException(FileParseException::class);
        $this->expectExceptionMessage('Invalid XML file format:');
        $this->parser->parse('<transactions><broken></transactions>');
    }

    public function test_it_throws_when_xml_does_not_contain_transactions(): void
    {
        $this->expectException(FileParseException::class);
        $this->expectExceptionMessage('Unable to locate transaction data in XML structure.');

        $this->parser->parse('<root><some_element>value</some_element></root>');
    }

    public function test_it_returns_empty_array_when_transactions_wrapper_is_empty(): void
    {
        $this->assertSame([], $this->parser->parse('<transactions></transactions>'));
        $this->assertSame([], $this->parser->parse('<transactions/>'));
    }

    public function test_it_handles_transaction_with_missing_fields(): void
    {
        $xml = <<<XML
            <transactions>
                <transaction>
                    <transaction_id>TRX203</transaction_id>
                    <account_number>PL12345678901234567890123456</account_number>
                    <amount>100.00</amount>
                </transaction>
            </transactions>
            XML;

        $records = $this->parser->parse($xml);

        $this->assertCount(1, $records);
        $this->assertSame('TRX203', $records[0]->transactionId);
        $this->assertSame('PL12345678901234567890123456', $records[0]->accountNumber);
        $this->assertSame('100.00', $records[0]->amount);
        $this->assertNull($records[0]->transactionDate);
        $this->assertNull($records[0]->currency);
    }

    public function test_it_handles_empty_transaction_element(): void
    {
        $xml = <<<XML
            <transactions>
                <transaction>
                    <transaction_id>TRX204</transaction_id>
                    <account_number>PL12345678901234567890123456</account_number>
                    <amount>100.00</amount>
                    <currency>PLN</currency>
                </transaction>
                <transaction></transaction>
            </transactions>
            XML;

        $records = $this->parser->parse($xml);

        $this->assertCount(2, $records);
        $this->assertSame('TRX204', $records[0]->transactionId);
        $this->assertNull($records[1]->transactionId);
        $this->assertNull($records[1]->accountNumber);
        $this->assertNull($records[1]->amount);
        $this->assertNull($records[1]->currency);
    }

    public function test_it_parses_sample_xml_structure(): void
    {
        $content = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <transactions>
                <transaction>
                    <transaction_id>550e8400-e29b-41d4-a716-446655440000</transaction_id>
                    <account_number>PL12345678901234567890123456</account_number>
                    <transaction_date>2025-10-14</transaction_date>
                    <amount>150000</amount>
                    <currency>PLN</currency>
                </transaction>
                <transaction>
                    <transaction_id>550e8400-e29b-41d4-a716-446655440001</transaction_id>
                    <account_number>PL98765432109876543210987654</account_number>
                    <transaction_date>2025-10-13</transaction_date>
                    <amount>20050</amount>
                    <currency>USD</currency>
                </transaction>
            </transactions>
            XML;

        $records = $this->parser->parse($content);

        $this->assertCount(2, $records);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $records[0]->transactionId);
        $this->assertSame('PL12345678901234567890123456', $records[0]->accountNumber);
        $this->assertSame('2025-10-14', $records[0]->transactionDate);
        $this->assertSame('150000', $records[0]->amount);
        $this->assertSame('PLN', $records[0]->currency);
    }
}
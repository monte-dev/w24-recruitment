<?php

namespace Tests\Unit\Parsers;

use App\Exceptions\FileParseException;
use App\Services\Parsers\CsvParser;
use PHPUnit\Framework\TestCase;

class CsvParserTest extends TestCase
{
    private CsvParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new CsvParser();
    }

    public function test_it_parses_comma_separated_csv(): void
    {
        $content = <<<CSV
            transaction_id,account_number,transaction_date,amount,currency
            TRX001,PL12345678901234567890123456,2026-01-15,100.50,PLN
            TRX002,PL98765432109876543210987654,2026-01-16,250.00,EUR
            CSV;

        $records = $this->parser->parse($content);

        $this->assertCount(2, $records);
        $this->assertSame('TRX001', $records[0]->transactionId);
        $this->assertSame('PL12345678901234567890123456', $records[0]->accountNumber);
        $this->assertSame('2026-01-15', $records[0]->transactionDate);
        $this->assertSame('100.50', $records[0]->amount);
        $this->assertSame('PLN', $records[0]->currency);
    }

    public function test_it_preserves_raw_amount_string(): void
    {
        $content = <<<CSV
            transaction_id,account_number,transaction_date,amount,currency
            TRX004,PL11112222333344445555666677,2026-02-01,"150,50",PLN
            CSV;

        $records = $this->parser->parse($content);

        $this->assertCount(1, $records);
        $this->assertSame('150,50', $records[0]->amount);
    }

    public function test_it_handles_utf8_bom(): void
    {
        $bom = pack('H*', 'EFBBBF');
        $csv = <<<CSV
            transaction_id,account_number,transaction_date,amount,currency
            TRX005,PL11112222333344445555666677,2026-02-01,99.99,PLN
            CSV;
        $content = $bom . $csv;

        $records = $this->parser->parse($content);

        $this->assertCount(1, $records);
        $this->assertSame('TRX005', $records[0]->transactionId);
    }

    public function test_it_returns_empty_array_for_empty_content(): void
    {
        $this->assertSame([], $this->parser->parse(''));
        $this->assertSame([], $this->parser->parse("   \n  "));
    }

    public function test_it_throws_exception_when_headers_are_missing(): void
    {
        $this->expectException(FileParseException::class);
        $this->expectExceptionMessage('CSV file is missing valid headers.');
        $this->parser->parse(",,,\n1,2,3,4");
    }

    public function test_it_throws_exception_on_duplicate_headers(): void
    {
        $this->expectException(FileParseException::class);
        $this->expectExceptionMessage('CSV file contains duplicate column headers.');

        $content = <<<CSV
            transaction_id,amount,amount,currency
            TRX001,100,200,PLN
            CSV;
        $this->parser->parse($content);
    }

    public function test_it_pads_row_with_fewer_columns_than_headers(): void
    {
        $content = <<<CSV
            transaction_id,account_number,transaction_date,amount,currency
            TRX010,PL11112222333344445555666677,2026-01-01,100.00
            CSV;

        $records = $this->parser->parse($content);

        $this->assertCount(1, $records);
        $this->assertSame('TRX010', $records[0]->transactionId);
        $this->assertNull($records[0]->currency);
    }

    public function test_it_truncates_row_with_more_columns_than_headers(): void
    {
        $content = <<<CSV
            transaction_id,account_number,transaction_date,amount,currency
            TRX011,PL11112222333344445555666677,2026-01-01,100.00,PLN,EXTRA_COLUMN
            CSV;

        $records = $this->parser->parse($content);

        $this->assertCount(1, $records);
        $this->assertSame('TRX011', $records[0]->transactionId);
        $this->assertSame('PLN', $records[0]->currency);
    }

    public function test_it_normalizes_uppercase_headers(): void
    {
        $content = <<<CSV
            TRANSACTION_ID,Account_Number,Transaction_Date,Amount,Currency
            TRX012,PL11112222333344445555666677,2026-01-01,100.00,PLN
            CSV;

        $records = $this->parser->parse($content);

        $this->assertCount(1, $records);
        $this->assertSame('TRX012', $records[0]->transactionId);
        $this->assertSame('PL11112222333344445555666677', $records[0]->accountNumber);
        $this->assertSame('2026-01-01', $records[0]->transactionDate);
        $this->assertSame('100.00', $records[0]->amount);
        $this->assertSame('PLN', $records[0]->currency);
    }

    public function test_it_skips_blank_lines_between_rows(): void
    {
        $content = <<<CSV
            transaction_id,account_number,transaction_date,amount,currency
            TRX013,PL11112222333344445555666677,2026-01-01,100.00,PLN

            TRX014,PL22223333444455556666777788,2026-01-02,200.00,EUR
            CSV;

        $records = $this->parser->parse($content);

        $this->assertCount(2, $records);
        $this->assertSame('TRX013', $records[0]->transactionId);
        $this->assertSame('TRX014', $records[1]->transactionId);
    }

    public function test_it_merges_fields_into_one_column_on_unclosed_quote(): void
    {
        $content = <<<CSV
            transaction_id,account_number,transaction_date,amount,currency
            TRX015,"PL11112222333344445555666677,2026-01-01,100.00,PLN
            CSV;

        $records = $this->parser->parse($content);

        $this->assertCount(1, $records);
        $this->assertSame('TRX015', $records[0]->transactionId);
        $this->assertSame(
            'PL11112222333344445555666677,2026-01-01,100.00,PLN',
            $records[0]->accountNumber
        );
        $this->assertNull($records[0]->transactionDate);
        $this->assertNull($records[0]->amount);
        $this->assertNull($records[0]->currency);
    }

    public function test_it_parses_sample_csv_structure(): void
    {
        $content = <<<CSV
            transaction_id,account_number,transaction_date,amount,currency
            550e8400-e29b-41d4-a716-446655440000,PL12345678901234567890123456,2025-10-14,150000,PLN
            550e8400-e29b-41d4-a716-446655440001,PL98765432109876543210987654,2025-10-13,20050,USD
            CSV;

        $records = $this->parser->parse($content);

        $this->assertCount(2, $records);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $records[0]->transactionId);
        $this->assertSame('PL12345678901234567890123456', $records[0]->accountNumber);
        $this->assertSame('2025-10-14', $records[0]->transactionDate);
        $this->assertSame('150000', $records[0]->amount);
        $this->assertSame('PLN', $records[0]->currency);
    }
}

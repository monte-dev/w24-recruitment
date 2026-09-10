<?php

namespace Tests\Feature\Services;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ImportService::class);
    }

    public function test_it_imports_valid_csv_successfully(): void
    {
        $csv = <<<CSV
            transaction_id,account_number,transaction_date,amount,currency
            550e8400-e29b-41d4-a716-446655440000,PL12345678901234567890123456,2025-10-14,150000,PLN
            550e8400-e29b-41d4-a716-446655440001,PL98765432109876543210987654,2025-10-13,20050,USD
            CSV;

        $import = $this->service->import('valid.csv', $csv, 'csv');

        $this->assertInstanceOf(Import::class, $import);
        $this->assertSame(ImportStatus::Success, $import->status);
        $this->assertSame(2, $import->total_records);
        $this->assertSame(2, $import->successful_records);
        $this->assertSame(0, $import->failed_records);

        $this->assertDatabaseCount('imports', 1);
        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseCount('import_logs', 0);

        $this->assertDatabaseHas('transactions', [
            'transaction_id' => '550e8400-e29b-41d4-a716-446655440000',
            'amount' => 150000.00,
            'currency' => 'PLN',
        ]);
    }

    public function test_it_imports_mixed_transactions_with_partial_status(): void
    {
        $csv = <<<CSV
            transaction_id,account_number,transaction_date,amount,currency
            550e8400-e29b-41d4-a716-446655440000,PL12345678901234567890123456,2025-10-14,150000,PLN
            ERR-BAD-IBAN,INVALID_ACCOUNT_123,2025-10-14,50000,PLN
            ERR-NEGATIVE-AMOUNT,PL98765432109876543210987654,2025-10-13,-200,USD
            ERR-WRONG-CURRENCY,PL11223344556677889900112233,2025-10-12,1200,EURO
            550e8400-e29b-41d4-a716-446655440001,PL98765432109876543210987654,2025-10-13,20050,USD
            CSV;

        $import = $this->service->import('mixed.csv', $csv, 'csv');

        $this->assertSame(ImportStatus::Partial, $import->status);
        $this->assertSame(5, $import->total_records);
        $this->assertSame(2, $import->successful_records);
        $this->assertSame(3, $import->failed_records);

        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseCount('import_logs', 3);

        $this->assertDatabaseHas('import_logs', [
            'import_id' => $import->id,
            'transaction_id' => 'ERR-BAD-IBAN',
        ]);
        $this->assertDatabaseHas('import_logs', [
            'import_id' => $import->id,
            'transaction_id' => 'ERR-NEGATIVE-AMOUNT',
        ]);
        $this->assertDatabaseHas('import_logs', [
            'import_id' => $import->id,
            'transaction_id' => 'ERR-WRONG-CURRENCY',
        ]);
    }

    public function test_it_marks_import_as_failed_when_all_records_are_invalid(): void
    {
        $csv = <<<CSV
            transaction_id,account_number,transaction_date,amount,currency
            ERR1,INVALID,2026-01-01,-100,BAD
            CSV;

        $import = $this->service->import('failed.csv', $csv, 'csv');

        $this->assertSame(ImportStatus::Failed, $import->status);
        $this->assertSame(1, $import->total_records);
        $this->assertSame(0, $import->successful_records);
        $this->assertSame(1, $import->failed_records);

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('import_logs', 1);
    }

    public function test_it_handles_empty_transactions_list_as_failed_import(): void
    {
        $import = $this->service->import('empty.json', '[]', 'json');

        $this->assertSame(ImportStatus::Failed, $import->status);
        $this->assertSame(0, $import->total_records);
        $this->assertSame(0, $import->successful_records);
        $this->assertSame(0, $import->failed_records);

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('import_logs', 0);
    }

    public function test_it_imports_json_and_xml_formats(): void
    {
        $json = json_encode([
            [
                'transaction_id' => 'JSON-TRX-1',
                'account_number' => 'PL12345678901234567890123456',
                'transaction_date' => '2026-05-01',
                'amount' => 500.00,
                'currency' => 'EUR',
            ],
        ]);

        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <transactions>
                <transaction>
                    <transaction_id>XML-TRX-1</transaction_id>
                    <account_number>PL98765432109876543210987654</account_number>
                    <transaction_date>2026-05-02</transaction_date>
                    <amount>750.25</amount>
                    <currency>USD</currency>
                </transaction>
            </transactions>
            XML;

        $jsonImport = $this->service->import('file.json', $json, 'json');
        $xmlImport = $this->service->import('file.xml', $xml, 'xml');

        $this->assertSame(ImportStatus::Success, $jsonImport->status);
        $this->assertSame(ImportStatus::Success, $xmlImport->status);
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_it_imports_via_uploaded_file(): void
    {
        $csv = <<<CSV
            transaction_id,account_number,transaction_date,amount,currency
            UPLOAD-1,PL12345678901234567890123456,2026-06-01,99.99,PLN
            CSV;

        $file = UploadedFile::fake()->createWithContent('upload.csv', $csv);

        $import = $this->service->importFile($file);

        $this->assertSame(ImportStatus::Success, $import->status);
        $this->assertSame('upload.csv', $import->file_name);
        $this->assertDatabaseHas('transactions', [
            'transaction_id' => 'UPLOAD-1',
        ]);
    }
}


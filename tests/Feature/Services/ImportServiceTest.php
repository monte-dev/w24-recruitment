<?php

namespace Tests\Feature\Services;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $import = $this->service->import(
            'valid_transactions.csv',
            $this->sampleContent('valid_transactions.csv'),
            'csv'
        );

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
        $import = $this->service->import(
            'mixed_transactions.csv',
            $this->sampleContent('mixed_transactions.csv'),
            'csv'
        );

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
        $jsonImport = $this->service->import(
            'valid_transactions.json',
            $this->sampleContent('valid_transactions.json'),
            'json'
        );
        $xmlImport = $this->service->import(
            'valid_transactions.xml',
            $this->sampleContent('valid_transactions.xml'),
            'xml'
        );

        $this->assertSame(ImportStatus::Success, $jsonImport->status);
        $this->assertSame(ImportStatus::Success, $xmlImport->status);
        $this->assertDatabaseCount('transactions', 4);
    }

    public function test_it_imports_via_uploaded_file(): void
    {
        $file = $this->sampleUploadedFile('valid_transactions.csv');

        $import = $this->service->importFile($file);

        $this->assertSame(ImportStatus::Success, $import->status);
        $this->assertSame('valid_transactions.csv', $import->file_name);
        $this->assertDatabaseHas('transactions', [
            'transaction_id' => '550e8400-e29b-41d4-a716-446655440000',
        ]);
    }
}

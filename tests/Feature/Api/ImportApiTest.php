<?php

namespace Tests\Feature\Api;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\ImportLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uploads_valid_csv_file(): void
    {
        $csv = <<<CSV
            transaction_id,account_number,transaction_date,amount,currency
            550e8400-e29b-41d4-a716-446655440000,PL12345678901234567890123456,2025-10-14,150000,PLN
            550e8400-e29b-41d4-a716-446655440001,PL98765432109876543210987654,2025-10-13,20050,USD
            CSV;

        $file = UploadedFile::fake()->createWithContent('valid.csv', $csv);

        $response = $this->postJson('/api/imports', [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.file_name', 'valid.csv')
            ->assertJsonPath('data.status', 'success')
            ->assertJsonPath('data.total_records', 2)
            ->assertJsonPath('data.successful_records', 2)
            ->assertJsonPath('data.failed_records', 0);

        $this->assertDatabaseCount('imports', 1);
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_it_uploads_mixed_csv_file_resulting_in_partial_status(): void
    {
        $csv = <<<CSV
            transaction_id,account_number,transaction_date,amount,currency
            550e8400-e29b-41d4-a716-446655440000,PL12345678901234567890123456,2025-10-14,150000,PLN
            ERR-BAD-IBAN,INVALID_ACCOUNT_123,2025-10-14,50000,PLN
            ERR-NEGATIVE-AMOUNT,PL98765432109876543210987654,2025-10-13,-200,USD
            ERR-WRONG-CURRENCY,PL11223344556677889900112233,2025-10-12,1200,EURO
            550e8400-e29b-41d4-a716-446655440001,PL98765432109876543210987654,2025-10-13,20050,USD
            CSV;

        $file = UploadedFile::fake()->createWithContent('mixed.csv', $csv);

        $response = $this->postJson('/api/imports', [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'partial')
            ->assertJsonPath('data.total_records', 5)
            ->assertJsonPath('data.successful_records', 2)
            ->assertJsonPath('data.failed_records', 3);

        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseCount('import_logs', 3);
    }

    public function test_it_uploads_json_file(): void
    {
        $json = json_encode([
            [
                'transaction_id' => 'JSON-1',
                'account_number' => 'PL12345678901234567890123456',
                'transaction_date' => '2026-06-01',
                'amount' => 500,
                'currency' => 'PLN',
            ],
        ]);

        $file = UploadedFile::fake()->createWithContent('transactions.json', $json);

        $response = $this->postJson('/api/imports', [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'success')
            ->assertJsonPath('data.successful_records', 1);
    }

    public function test_it_uploads_xml_file(): void
    {
        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <transactions>
                <transaction>
                    <transaction_id>XML-1</transaction_id>
                    <account_number>PL12345678901234567890123456</account_number>
                    <transaction_date>2026-06-01</transaction_date>
                    <amount>1200.50</amount>
                    <currency>EUR</currency>
                </transaction>
            </transactions>
            XML;

        $file = UploadedFile::fake()->createWithContent('transactions.xml', $xml);

        $response = $this->postJson('/api/imports', [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'success')
            ->assertJsonPath('data.successful_records', 1);
    }

    public function test_it_rejects_unsupported_file_format(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/imports', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_it_rejects_request_without_file(): void
    {
        $response = $this->postJson('/api/imports', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_it_returns_422_when_csv_has_missing_headers(): void
    {
        $file = UploadedFile::fake()->createWithContent('broken.csv', ",,,\n1,2,3");

        $response = $this->postJson('/api/imports', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'CSV file is missing valid headers.',
            ]);
    }

    public function test_it_returns_422_when_json_has_syntax_error(): void
    {
        $file = UploadedFile::fake()->createWithContent('broken.json', '{invalid-json}');

        $response = $this->postJson('/api/imports', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_it_returns_list_of_imports(): void
    {
        Import::create([
            'file_name' => 'first.csv',
            'total_records' => 5,
            'successful_records' => 5,
            'failed_records' => 0,
            'status' => ImportStatus::Success,
        ]);

        Import::create([
            'file_name' => 'second.csv',
            'total_records' => 3,
            'successful_records' => 1,
            'failed_records' => 2,
            'status' => ImportStatus::Partial,
        ]);

        $response = $this->getJson('/api/imports');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.file_name', 'second.csv')
            ->assertJsonPath('data.1.file_name', 'first.csv');
    }

    public function test_it_returns_import_details_with_logs(): void
    {
        $import = Import::create([
            'file_name' => 'with_errors.csv',
            'total_records' => 2,
            'successful_records' => 0,
            'failed_records' => 2,
            'status' => ImportStatus::Failed,
        ]);

        ImportLog::create([
            'import_id' => $import->id,
            'transaction_id' => 'ERR-1',
            'error_message' => 'Invalid IBAN',
        ]);

        ImportLog::create([
            'import_id' => $import->id,
            'transaction_id' => 'ERR-2',
            'error_message' => 'Negative amount',
        ]);

        $response = $this->getJson("/api/imports/{$import->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $import->id)
            ->assertJsonPath('data.file_name', 'with_errors.csv')
            ->assertJsonCount(2, 'data.logs')
            ->assertJsonPath('data.logs.0.transaction_id', 'ERR-1')
            ->assertJsonPath('data.logs.1.transaction_id', 'ERR-2');
    }

    public function test_it_returns_404_when_import_not_found(): void
    {
        $response = $this->getJson('/api/imports/99999');

        $response->assertStatus(404);
    }
}

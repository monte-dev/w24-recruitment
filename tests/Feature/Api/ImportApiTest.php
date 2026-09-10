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
        $response = $this->postJson('/api/imports', [
            'file' => $this->sampleUploadedFile('valid_transactions.csv'),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.file_name', 'valid_transactions.csv')
            ->assertJsonPath('data.status', 'success')
            ->assertJsonPath('data.total_records', 2)
            ->assertJsonPath('data.successful_records', 2)
            ->assertJsonPath('data.failed_records', 0);

        $this->assertDatabaseCount('imports', 1);
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_it_uploads_mixed_csv_file_resulting_in_partial_status(): void
    {
        $response = $this->postJson('/api/imports', [
            'file' => $this->sampleUploadedFile('mixed_transactions.csv'),
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
        $response = $this->postJson('/api/imports', [
            'file' => $this->sampleUploadedFile('valid_transactions.json'),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'success')
            ->assertJsonPath('data.successful_records', 2);
    }

    public function test_it_uploads_xml_file(): void
    {
        $response = $this->postJson('/api/imports', [
            'file' => $this->sampleUploadedFile('valid_transactions.xml'),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'success')
            ->assertJsonPath('data.successful_records', 2);
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

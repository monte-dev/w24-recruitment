<?php

namespace Tests\Feature\Repositories;

use App\DTO\TransactionData;
use App\Enums\ImportStatus;
use App\Models\Import;
use App\Repositories\EloquentImportRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentImportRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentImportRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentImportRepository();
    }

    public function test_it_creates_import_record(): void
    {
        $import = $this->repository->create([
            'file_name' => 'test.csv',
            'total_records' => 10,
            'successful_records' => 8,
            'failed_records' => 2,
            'status' => ImportStatus::Partial,
        ]);

        $this->assertInstanceOf(Import::class, $import);
        $this->assertDatabaseHas('imports', [
            'id' => $import->id,
            'file_name' => 'test.csv',
            'status' => 'partial',
        ]);
    }

    public function test_it_creates_transaction_with_float_amount(): void
    {
        $dto = new TransactionData(
            transactionId: 'TRX001',
            accountNumber: 'PL12345678901234567890123456',
            transactionDate: '2026-01-15',
            amount: '150,50',
            currency: 'PLN',
        );

        $transaction = $this->repository->createTransaction($dto);

        $this->assertSame('TRX001', $transaction->transaction_id);
        $this->assertDatabaseHas('transactions', [
            'transaction_id' => 'TRX001',
            'amount' => 150.50,
            'currency' => 'PLN',
        ]);
    }

    public function test_it_creates_log_associated_with_import(): void
    {
        $import = $this->repository->create([
            'file_name' => 'test.csv',
            'total_records' => 1,
            'successful_records' => 0,
            'failed_records' => 1,
            'status' => ImportStatus::Failed,
        ]);

        $log = $this->repository->createLog($import, 'TRX_ERR', 'Invalid IBAN format.');

        $this->assertSame($import->id, $log->import_id);
        $this->assertDatabaseHas('import_logs', [
            'import_id' => $import->id,
            'transaction_id' => 'TRX_ERR',
            'error_message' => 'Invalid IBAN format.',
        ]);
    }

    public function test_all_returns_newest_imports_first(): void
    {
        $first = $this->repository->create([
            'file_name' => 'first.csv',
            'status' => ImportStatus::Success,
        ]);

        $second = $this->repository->create([
            'file_name' => 'second.csv',
            'status' => ImportStatus::Success,
        ]);

        $all = $this->repository->all();

        $this->assertCount(2, $all);
        $this->assertSame($second->id, $all->first()->id);
        $this->assertSame($first->id, $all->last()->id);
    }

    public function test_find_by_id_with_logs_eager_loads_logs(): void
    {
        $import = $this->repository->create([
            'file_name' => 'logged.csv',
            'status' => ImportStatus::Failed,
        ]);

        $this->repository->createLog($import, 'ERR1', 'Error 1');
        $this->repository->createLog($import, 'ERR2', 'Error 2');

        $found = $this->repository->findByIdWithLogs($import->id);

        $this->assertNotNull($found);
        $this->assertTrue($found->relationLoaded('logs'));
        $this->assertCount(2, $found->logs);
    }

    public function test_find_by_id_with_logs_returns_null_when_not_found(): void
    {
        $found = $this->repository->findByIdWithLogs(99999);

        $this->assertNull($found);
    }
}

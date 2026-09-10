<?php

namespace App\Repositories;

use App\DTO\TransactionData;
use App\Models\Import;
use App\Models\ImportLog;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

class EloquentImportRepository implements ImportRepositoryInterface
{
    public function create(array $data): Import
    {
        return Import::create($data);
    }

    public function createTransaction(TransactionData $dto): Transaction
    {
        return Transaction::create([
            'transaction_id' => $dto->transactionId,
            'account_number' => $dto->accountNumber,
            'transaction_date' => $dto->transactionDate,
            'amount' => $dto->amountAsFloat(),
            'currency' => $dto->currency,
        ]);
    }

    public function createLog(Import $import, ?string $transactionId, string $errorMessage): ImportLog
    {
        return $import->logs()->create([
            'transaction_id' => $transactionId,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * @return Collection<int, Import>
     */
    public function all(): Collection
    {
        return Import::latest('id')->get();
    }

    public function findByIdWithLogs(int $id): ?Import
    {
        return Import::with('logs')->find($id);
    }
}

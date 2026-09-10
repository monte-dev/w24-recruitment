<?php

namespace App\Repositories;

use App\DTO\TransactionData;
use App\Models\Import;
use App\Models\ImportLog;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

interface ImportRepositoryInterface
{
    public function create(array $data): Import;

    public function createTransaction(TransactionData $dto): Transaction;

    public function createLog(Import $import, ?string $transactionId, string $errorMessage): ImportLog;

    /**
     * @return Collection<int, Import>
     */
    public function all(): Collection;

    public function findByIdWithLogs(int $id): ?Import;
}

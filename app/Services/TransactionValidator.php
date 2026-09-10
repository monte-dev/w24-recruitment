<?php

namespace App\Services;

use App\DTO\TransactionData;
use DateTimeImmutable;

class TransactionValidator
{
    public function validate(TransactionData $dto): ?string
    {
        $errors = [];

        if ($dto->transactionId === null || $dto->transactionId === '') {
            $errors[] = 'Transaction ID is required.';
        }

        if ($dto->accountNumber === null || !preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{4,30}$/', $dto->accountNumber)) {
            $errors[] = 'Invalid account number format (valid IBAN required).';
        }

        if ($dto->transactionDate === null || $dto->transactionDate === '') {
            $errors[] = 'Transaction date is required.';
        } else {
            $date = DateTimeImmutable::createFromFormat('Y-m-d', $dto->transactionDate);
            if (!$date || $date->format('Y-m-d') !== $dto->transactionDate) {
                $errors[] = 'Transaction date must be in Y-m-d format.';
            }
        }

        if ($dto->amount === null) {
            $errors[] = 'Amount is required.';
        } else {
            $amount = $dto->amountAsFloat();
            if ($amount === null || $amount <= 0) {
                $errors[] = 'Amount must be a number greater than 0.';
            }
        }

        if ($dto->currency === null || !preg_match('/^[A-Z]{3}$/', $dto->currency)) {
            $errors[] = 'Currency must be a 3-letter ISO code.';
        }

        if (empty($errors)) {
            return null;
        }

        return implode('; ', $errors);
    }
}

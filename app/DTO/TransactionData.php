<?php

namespace App\DTO;

readonly class TransactionData
{
    public function __construct(
        public ?string $transactionId,
        public ?string $accountNumber,
        public ?string $transactionDate,
        public string|int|float|null $amount,
        public ?string $currency,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: self::nullableString($data, 'transaction_id'),
            accountNumber: self::nullableString($data, 'account_number'),
            transactionDate: self::nullableString($data, 'transaction_date'),
            amount: self::sanitizeRawAmount($data['amount'] ?? null),
            currency: self::nullableString($data, 'currency', toUpper: true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'account_number' => $this->accountNumber,
            'transaction_date' => $this->transactionDate,
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }

    public function amountAsFloat(): ?float
    {
        if ($this->amount === null) {
            return null;
        }

        $value = is_string($this->amount)
            ? str_replace(',', '.', $this->amount)
            : $this->amount;

        return is_numeric($value) ? (float) $value : null;
    }

    private static function nullableString(array $data, string $key, bool $toUpper = false): ?string
    {
        $value = $data[$key] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        $trimmed = trim((string) $value);

        return $toUpper ? strtoupper($trimmed) : $trimmed;
    }

    private static function sanitizeRawAmount(mixed $amount): string|int|float|null
    {
        if ($amount === null || $amount === '' || is_bool($amount) || is_array($amount)) {
            return null;
        }

        if (is_int($amount) || is_float($amount)) {
            return $amount;
        }

        $trimmed = trim((string) $amount);

        return $trimmed === '' ? null : $trimmed;
    }
}

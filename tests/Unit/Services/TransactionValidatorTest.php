<?php

namespace Tests\Unit\Services;

use App\DTO\TransactionData;
use App\Services\TransactionValidator;
use PHPUnit\Framework\TestCase;

class TransactionValidatorTest extends TestCase
{
    private TransactionValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new TransactionValidator();
    }

    public function test_it_passes_valid_transaction(): void
    {
        $dto = new TransactionData(
            transactionId: 'TRX001',
            accountNumber: 'PL12345678901234567890123456',
            transactionDate: '2026-01-15',
            amount: 150.50,
            currency: 'PLN',
        );

        $this->assertNull($this->validator->validate($dto));
    }

    public function test_it_passes_valid_transaction_with_integer_amount(): void
    {
        $dto = new TransactionData(
            transactionId: 'TRX002',
            accountNumber: 'DE89370400440532013000',
            transactionDate: '2026-02-20',
            amount: 150000,
            currency: 'EUR',
        );

        $this->assertNull($this->validator->validate($dto));
    }

    public function test_it_passes_valid_transaction_with_comma_in_amount_string(): void
    {
        $dto = new TransactionData(
            transactionId: 'TRX003',
            accountNumber: 'GB29NWBK60161331926819',
            transactionDate: '2026-03-10',
            amount: '200,50',
            currency: 'GBP',
        );

        $this->assertNull($this->validator->validate($dto));
    }

    public function test_it_fails_when_transaction_id_is_empty(): void
    {
        $dto = new TransactionData(
            transactionId: null,
            accountNumber: 'PL12345678901234567890123456',
            transactionDate: '2026-01-15',
            amount: 100.00,
            currency: 'PLN',
        );

        $error = $this->validator->validate($dto);

        $this->assertNotNull($error);
        $this->assertStringContainsString('Transaction ID is required', $error);
    }

    public function test_it_fails_when_account_number_is_not_valid_iban(): void
    {
        $dto = new TransactionData(
            transactionId: 'TRX004',
            accountNumber: 'INVALID_ACCOUNT_123',
            transactionDate: '2026-01-15',
            amount: 100.00,
            currency: 'PLN',
        );

        $error = $this->validator->validate($dto);

        $this->assertNotNull($error);
        $this->assertStringContainsString('Invalid account number format', $error);
    }

    public function test_it_fails_when_amount_is_zero_or_negative(): void
    {
        $dtoZero = new TransactionData(
            transactionId: 'TRX005',
            accountNumber: 'PL12345678901234567890123456',
            transactionDate: '2026-01-15',
            amount: 0,
            currency: 'PLN',
        );

        $dtoNegative = new TransactionData(
            transactionId: 'TRX006',
            accountNumber: 'PL12345678901234567890123456',
            transactionDate: '2026-01-15',
            amount: -50.00,
            currency: 'PLN',
        );

        $this->assertStringContainsString('Amount must be a number greater than 0', (string) $this->validator->validate($dtoZero));
        $this->assertStringContainsString('Amount must be a number greater than 0', (string) $this->validator->validate($dtoNegative));
    }

    public function test_it_fails_when_amount_is_not_numeric(): void
    {
        $dto = new TransactionData(
            transactionId: 'TRX007',
            accountNumber: 'PL12345678901234567890123456',
            transactionDate: '2026-01-15',
            amount: 'not-a-number',
            currency: 'PLN',
        );

        $error = $this->validator->validate($dto);

        $this->assertNotNull($error);
        $this->assertStringContainsString('Amount must be a number greater than 0', $error);
    }

    public function test_it_fails_when_currency_is_not_3_letters(): void
    {
        $dtoLong = new TransactionData(
            transactionId: 'TRX008',
            accountNumber: 'PL12345678901234567890123456',
            transactionDate: '2026-01-15',
            amount: 100.00,
            currency: 'EURO',
        );

        $dtoShort = new TransactionData(
            transactionId: 'TRX009',
            accountNumber: 'PL12345678901234567890123456',
            transactionDate: '2026-01-15',
            amount: 100.00,
            currency: 'PL',
        );

        $this->assertStringContainsString('Currency must be a 3-letter ISO code', (string) $this->validator->validate($dtoLong));
        $this->assertStringContainsString('Currency must be a 3-letter ISO code', (string) $this->validator->validate($dtoShort));
    }

    public function test_it_fails_when_transaction_date_is_invalid(): void
    {
        $dto = new TransactionData(
            transactionId: 'TRX010',
            accountNumber: 'PL12345678901234567890123456',
            transactionDate: '2026-13-45',
            amount: 100.00,
            currency: 'PLN',
        );

        $error = $this->validator->validate($dto);

        $this->assertNotNull($error);
        $this->assertStringContainsString('Transaction date must be in Y-m-d format', $error);
    }

    public function test_it_combines_multiple_validation_errors(): void
    {
        $dto = new TransactionData(
            transactionId: null,
            accountNumber: 'BAD_IBAN',
            transactionDate: 'BAD_DATE',
            amount: -10,
            currency: 'TOOLONG',
        );

        $error = (string) $this->validator->validate($dto);

        $this->assertStringContainsString('Transaction ID is required', $error);
        $this->assertStringContainsString('Invalid account number format', $error);
        $this->assertStringContainsString('Transaction date must be in Y-m-d format', $error);
        $this->assertStringContainsString('Amount must be a number greater than 0', $error);
        $this->assertStringContainsString('Currency must be a 3-letter ISO code', $error);
        $this->assertStringContainsString('; ', $error);
    }
}

<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Exceptions\FileParseException;
use App\Models\Import;
use App\Repositories\ImportRepositoryInterface;
use App\Services\Parsers\FileParserFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ImportService
{
    public function __construct(
        private readonly FileParserFactory $parserFactory,
        private readonly ImportRepositoryInterface $repository,
        private readonly TransactionValidator $validator,
    ) {}

    public function importFile(UploadedFile $file): Import
    {
        return $this->import(
            fileName: $file->getClientOriginalName(),
            content: (string) $file->get(),
            extension: $file->getClientOriginalExtension(),
        );
    }

    /**
     * @throws FileParseException
     */
    public function import(string $fileName, string $content, string $extension): Import
    {
        $parser = $this->parserFactory->make($extension);
        $records = $parser->parse($content);

        return DB::transaction(function () use ($fileName, $records) {
            $total = count($records);
            $successful = 0;
            $failed = 0;
            $logs = [];
            $validDtos = [];

            foreach ($records as $dto) {
                $errorMessage = $this->validator->validate($dto);

                if ($errorMessage === null) {
                    $validDtos[] = $dto;
                    $successful++;
                } else {
                    $logs[] = [
                        'transaction_id' => $dto->transactionId,
                        'error_message' => $errorMessage,
                    ];
                    $failed++;
                }
            }

            // Empty file (no records) treated as failed, import did not provide any data
            if ($total === 0) {
                $status = ImportStatus::Failed;
            } elseif ($failed === 0) {
                $status = ImportStatus::Success;
            } elseif ($successful === 0) {
                $status = ImportStatus::Failed;
            } else {
                $status = ImportStatus::Partial;
            }

            $import = $this->repository->create([
                'file_name' => $fileName,
                'total_records' => $total,
                'successful_records' => $successful,
                'failed_records' => $failed,
                'status' => $status,
            ]);

            foreach ($validDtos as $dto) {
                $this->repository->createTransaction($dto);
            }

            foreach ($logs as $log) {
                $this->repository->createLog($import, $log['transaction_id'], $log['error_message']);
            }

            return $import;
        });
    }
}

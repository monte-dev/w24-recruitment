<?php

namespace App\Services\Parsers;

use App\DTO\TransactionData;
use App\Exceptions\FileParseException;
use App\Services\Parsers\Concerns\StripsBom;

class CsvParser implements FileParserInterface
{
    use StripsBom;

    private const DELIMITER = ',';

    /**
     * @return array<TransactionData>
     */
    public function parse(string $content): array
    {
        $content = $this->stripBom($content);

        if (trim($content) === '') {
            return [];
        }

        $stream = $this->createStream($content);

        try {
            $headers = $this->extractHeaders($stream);

            return $this->parseRows($stream, $headers);
        } finally {
            fclose($stream);
        }
    }

    /**
     * @throws FileParseException
     */
    private function createStream(string $content)
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new FileParseException('Unable to open memory stream for CSV parsing.');
        }

        fwrite($stream, $content);
        rewind($stream);

        return $stream;
    }

    /**
     * @throws FileParseException
     */
    private function extractHeaders($stream): array
    {
        $headers = fgetcsv($stream, 0, self::DELIMITER, '"', '');

        if ($headers === false || empty(array_filter($headers, fn($h) => trim((string) $h) !== ''))) {
            throw new FileParseException('CSV file is missing valid headers.');
        }

        $normalizedHeaders = array_map(
            fn($header) => strtolower(trim((string) $header)),
            $headers
        );

        if (count($normalizedHeaders) !== count(array_unique($normalizedHeaders))) {
            throw new FileParseException('CSV file contains duplicate column headers.');
        }

        return $normalizedHeaders;
    }

    private function parseRows($stream, array $headers): array
    {
        $records = [];
        $headerCount = count($headers);

        while (($row = fgetcsv($stream, 0, self::DELIMITER, '"', '')) !== false) {
            if (count($row) === 1 && $row[0] === null) {
                continue;
            }

            $rowCount = count($row);

            if ($rowCount < $headerCount) {
                $row = array_pad($row, $headerCount, null);
            } elseif ($rowCount > $headerCount) {
                $row = array_slice($row, 0, $headerCount);
            }

            $records[] = TransactionData::fromArray(array_combine($headers, $row));
        }

        return $records;
    }
}

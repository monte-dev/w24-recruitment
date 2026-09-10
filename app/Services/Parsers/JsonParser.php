<?php

namespace App\Services\Parsers;

use App\DTO\TransactionData;
use App\Exceptions\FileParseException;
use App\Services\Parsers\Concerns\StripsBom;
use JsonException;

class JsonParser implements FileParserInterface
{
    use StripsBom;

    /**
     * @return array<TransactionData>
     * @throws FileParseException
     */
    public function parse(string $content): array
    {
        $content = $this->stripBom($content);

        if (trim($content) === '') {
            return [];
        }

        $items = $this->decodeJson($content);

        return $this->mapToDtos($items);
    }

    /**
     * @return array<mixed>
     * @throws FileParseException
     */
    private function decodeJson(string $content): array
    {
        try {
            $data = json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new FileParseException('Invalid JSON file format: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($data) || !array_is_list($data)) {
            throw new FileParseException('JSON file must contain a list of transactions.');
        }

        return $data;
    }

    /**
     * @param array<mixed> $items
     * @return array<TransactionData>
     */
    private function mapToDtos(array $items): array
    {
        $records = [];

        foreach ($items as $item) {
            $records[] = TransactionData::fromArray(is_array($item) ? $item : []);
        }

        return $records;
    }
}

<?php

namespace App\Services\Parsers;

use App\DTO\TransactionData;
use App\Exceptions\FileParseException;
use App\Services\Parsers\Concerns\StripsBom;
use SimpleXMLElement;

class XmlParser implements FileParserInterface
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

        $xml = $this->loadXml($content);
        $elements = $this->extractElements($xml);

        return array_map(fn (SimpleXMLElement $element) => $this->elementToDto($element), $elements);
    }

    /**
     * @throws FileParseException
     */
    private function loadXml(string $content): SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($xml === false) {
            $errors = libxml_get_errors();
            $message = !empty($errors) ? trim($errors[0]->message) : 'XML syntax error';
            libxml_clear_errors();

            throw new FileParseException('Invalid XML file format: ' . $message);
        }

        libxml_clear_errors();

        return $xml;
    }

    /**
     * @return array<SimpleXMLElement>
     * @throws FileParseException
     */
    private function extractElements(SimpleXMLElement $xml): array
    {
        if (strtolower($xml->getName()) !== 'transactions') {
            throw new FileParseException('Unable to locate transaction data in XML structure.');
        }

        $elements = [];

        foreach ($xml->transaction as $item) {
            $elements[] = $item;
        }

        return $elements;
    }

    private function elementToDto(SimpleXMLElement $element): TransactionData
    {
        $data = [];

        foreach ($element->children() as $child) {
            $data[strtolower($child->getName())] = (string) $child;
        }

        return TransactionData::fromArray($data);
    }
}

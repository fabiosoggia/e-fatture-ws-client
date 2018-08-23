<?php

namespace CloudFinance\EFattureWsClient;

require_once __DIR__ . "/../vendor/autoload.php";

use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;
use CloudFinance\EFattureWsClient\Exceptions\InvalidInvoice;
use CloudFinance\EFattureWsClient\Exceptions\InvalidInvoiceParameter;
use CloudFinance\EFattureWsClient\Iso3166;

define("FATTURA_PA_1_2_NAMESPACE", "http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2");

class InvoiceBuilder
{
    protected $domDocument;
    protected $domXPath;
    protected $rootNode;
    protected $fatturaPaNamespaceUri;
    protected $fatturaPaNamespacePrefix;

    public function __construct(sring $xml = null) {
        if (empty($xml)) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>
                <p:FatturaElettronica versione="FPA12"
                    xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
                    xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                    xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">
                </p:FatturaElettronica>';
        }

        $this->domDocument = \DOMDocument::loadXML($xml);
        $this->rootNode = $this->domDocument->documentElement;

        $this->domXPath = new \DOMXPath($this->domDocument);

        $namespaces = [];
        $hasDefaultNamespace = false;
        $hasFatturaPaNamespacePrefix = false;
        foreach ($this->domXPath->query('namespace::*') as $node) {
            $namespaceUri = $node->nodeValue;
            $namespacePrefix = $this->domDocument->lookupPrefix($namespaceUri);
            $namespaces[] = $namespaceUri;

            $hasDefaultNamespace = $hasDefaultNamespace || $this->domDocument->isDefaultNamespace($namespaceUri);
            $hasFatturaPaNamespacePrefix = $hasFatturaPaNamespacePrefix
                || (($namespaceUri === FATTURA_PA_1_2_NAMESPACE) && (!empty($namespacePrefix)));
        }

        if (!$hasDefaultNamespace) {
            $this->rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', FATTURA_PA_1_2_NAMESPACE);
            // $this->domXPath->registerNamespace("", FATTURA_PA_1_2_NAMESPACE);
        }

        $namespacePrefix = "";
        if (!$hasFatturaPaNamespacePrefix) {
            $namespacePrefixExists = false;
            do {
                $namespacePrefix .= "p";
                $namespacePrefixExists = !empty($this->domDocument->lookupNamespaceUri($namespacePrefix));
            } while ($namespacePrefixExists);
            $this->rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:' . $namespacePrefix, FATTURA_PA_1_2_NAMESPACE);
        } else {
            $namespacePrefix = $this->domDocument->lookupPrefix(FATTURA_PA_1_2_NAMESPACE);
        }

        $this->fatturaPaNamespaceUri = FATTURA_PA_1_2_NAMESPACE;
        $this->fatturaPaNamespacePrefix = $namespacePrefix;

        $this->domDocument = \DOMDocument::loadXML($this->domDocument->saveXML());
        $this->rootNode = $this->domDocument->documentElement;
        $this->domXPath = new \DOMXPath($this->domDocument);
    }

    public function validate()
    {
        $this->domDocument->normalizeDocument();
        $internalErrorPreviousValue = libxml_use_internal_errors(true);
        $schema = __DIR__ . "/../resources/Schema_del_file_xml_FatturaPA_versione_1.2.xsd";
        if ($this->domDocument->schemaValidate($schema)) {
            libxml_use_internal_errors($internalErrorPreviousValue);
            return;
        }

        $error = libxml_get_last_error();
        libxml_use_internal_errors($internalErrorPreviousValue);
        throw new InvalidInvoice($error->message, $error->code);
    }

    public function set(string $xpath, $value)
    {
        $parentNode = $this->rootNode;
        $currentPath = $this->rootNode->getNodePath();

        $namespacePrefix = $this->fatturaPaNamespacePrefix;
        $namespaceUri = $this->fatturaPaNamespaceUri;

        $xpath = trim($xpath, "/");
        $tags = \explode('/', $xpath);

        $tagRegex = '/([a-zA-Z0-9]+)(\[(\d*)\])?/i';

        foreach ($tags as $token) {
            $res = preg_match($tagRegex, $token, $matches);
            if ($res === false) {
                throw \Exception("Invalid regex.");
            }

            if ($res === 0) {
                $error = sprintf("Could not parse path '%s'.", $token);
                throw new EFattureWsClientException($error);
            }

            $tag = $matches[1];
            $index = count($matches) === 4 ? intval($matches[3]) : 1;
            $index = \max($index, 1);

            if (empty($tag)) {
                continue;
            }

            if ($tag === 'FatturaElettronica') {
                continue;
            }

            $currentPath .= "/$namespacePrefix:$tag";
            $nodes = $this->domXPath->query($currentPath);
            $nodesCount = \count($nodes);

            if ($nodesCount > $index - 1) {
                $node = $nodes->item($index - 1);
            } else {
                for ($n = $nodesCount; $n <= $index; $n++) {
                    $node = $this->domDocument->createElementNS($namespaceUri, $tag);
                    $parentNode->appendChild($node);
                }
            }

            $currentPath = $node->getNodePath();
            $parentNode = $node;
        }

        $parentNode->nodeValue = $value;
    }

    public function has(string $xpath)
    {
        $nodes = $this->domXPath->query($xpath);
        if (\count($nodes) > 0) {
            return true;
        }
        return false;
    }

    public function get(string $xpath, $default = null)
    {
        $nodes = $this->domXPath->query($xpath);
        if (\count($nodes) !== 1) {
            return $default;
        }
        return $nodes->item(0)->nodeValue;
    }

    public function saveXML()
    {
        return $this->domDocument->saveXML();
    }
}

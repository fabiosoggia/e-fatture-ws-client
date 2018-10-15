<?php

namespace CloudFinance\EFattureWsClient\V1\Xml;

use \DOMDocument;
use \DOMXPath;


// Example:
//
// $outputFilePath = __DIR__ . "/../../../resources/output.csv";
// $xsdPath = __DIR__ . "/../../../resources/Schema_VFPR12.xsd";
// $parser = new XSDSchemaParser($xsdPath);
// $outputFile = fopen($outputFilePath, "w");
// $parser->toCSVFile($outputFile);
// fclose($outputFile);


/**
 * Questa classe permette di estrapolare informazioni strutturali descritte
 * nei file XSD.
 */
class XSDSchemaParser {

    private $xsdPath;
    private $domDocument;
    private $domDocumentXPath;
    private $simpleTypes;
    private $attributesHeaders;
    private $complexTypesArray;

    public function __construct($xsdPath) {
        $this->xsdPath = $xsdPath;

        $this->domDocument = new DOMDocument();
        $this->domDocument->loadXML(file_get_contents($this->xsdPath));
        $this->domDocumentXPath = new DOMXPath($this->domDocument);

        $this->initSimpleTypes();
        $this->initHeaders();
        $this->initComplexTypes();
        $this->parse();
    }

    public function parseSimpleTypeNode($node)
    {
        $name = $node->attributes->getNamedItem("name")->nodeValue;
        $attributeNodes = $this->domDocumentXPath->query(".//@*", $node);
        $attributes = [];
        $enumValues = [];
        for ($i = 0; $i < $attributeNodes->length; $i++) {
            $attributeNode = $attributeNodes->item($i);
            $attributeName = $attributeNode->name;
            $attributeValue = $attributeNode->value;
            $attributeTag = $attributeNode->ownerElement->localName;
            $attributes[ "$attributeTag.$attributeName" ] = $attributeValue;

            if ($attributeTag === "enumeration") {
                $enumValues[] = $attributeValue;
            }
        }
        $attributes["enumeration.value"] = implode(", ", $enumValues);
        $result = [];
        $result[$name] = $attributes;
        return $result;
    }

    public function initSimpleTypes()
    {
        $simpleTypeNodes = $this->domDocument->getElementsByTagNameNS("http://www.w3.org/2001/XMLSchema", "simpleType");
        $simpleTypes = [];
        for ($i = 0; $i < $simpleTypeNodes->length; $i++) {
            $node = $simpleTypeNodes->item($i);
            $simpleType = $simpleTypes + $this->parseSimpleTypeNode($node);
            foreach ($simpleType as $value) {
                $keys = array_keys($value);
            }

            $simpleTypes = $simpleTypes + $simpleType;
        }

        $this->simpleTypes = $simpleTypes;
    }

    public function initHeaders()
    {
        $attributesHeaders = [];
        foreach ($this->simpleTypes as $simpleType) {
            $keys = array_keys($simpleType);
            $attributesHeaders = array_merge($attributesHeaders, $keys);
        }
        $attributesHeaders = array_unique($attributesHeaders);
        array_unshift($attributesHeaders, "path");
        $this->attributesHeaders = $attributesHeaders;

    }

    public function initComplexTypes()
    {
        $complexTypeNodes  = $this->domDocument->getElementsByTagNameNS("http://www.w3.org/2001/XMLSchema", "complexType");
        $complexTypesArray = [];
        for ($i = 0; $i < $complexTypeNodes->length; $i++) {
            $node = $complexTypeNodes->item($i);
            $name = $node->attributes->getNamedItem("name")->nodeValue;
            $complexTypesArray[$name] = $node;
        }
        $this->complexTypesArray = $complexTypesArray;
    }

    public function walk($node)
    {
        if (!$node->attributes->getNamedItem("type")) {
            echo "Skipped node:\n" . ($this->domDocument->saveXML($node)) . "\n\n";
            return [];
        }

        $type = $node->attributes->getNamedItem("type")->nodeValue;
        $name = $node->attributes->getNamedItem("name")->nodeValue;
        $n = false;

        $minOccurs = 0;
        if ($node->attributes->getNamedItem("minOccurs")) {
            $minOccurs = intval($node->attributes->getNamedItem("minOccurs")->nodeValue);
        }
        $maxOccurs = 1;
        if ($node->attributes->getNamedItem("maxOccurs")) {
            $maxOccursValue = $node->attributes->getNamedItem("maxOccurs")->nodeValue;

            if (is_numeric($maxOccursValue)) {
                $maxOccurs = intval($maxOccursValue);
            }

            if ($maxOccursValue === "unbounded") {
                $maxOccurs = "unbounded";
            }
        }
        $n = ($maxOccurs === "unbounded" || $maxOccurs > 1) ? "[n]" : "";

        if (array_key_exists($type, $this->simpleTypes)) {
            return [
                "$name" => $this->simpleTypes[$type]
            ];
        }

        $results = [];
        if (array_key_exists($type, $this->complexTypesArray)) {
            $elementNodes = $this->domDocumentXPath->query(".//*[local-name() = 'element']", $this->complexTypesArray[$type]);
            for ($i = 0; $i < $elementNodes->length; $i++) {
                $elementNode = $elementNodes->item($i);
                $children = $this->walk($elementNode);
                foreach ($children as $path => $value) {
                    $results["$name$n/$path"] = $value;
                }
            }
            return $results;
        }

        return [ "$name" => [] ];
    }

    public function parse()
    {
        $elementNodes  = $this->domDocument->getElementsByTagNameNS("http://www.w3.org/2001/XMLSchema", "element");
        $root = $elementNodes->item(0);
        $res = $this->walk($root);

        $this->values = [];
        foreach ($res as $path => $attributes) {
            $normalizedValues = [];
            foreach ($this->attributesHeaders as $header) {
                $normalizedValues[] = isset($attributes[$header]) ? $attributes[$header] : "";
            }
            array_unshift($normalizedValues, $path);
            $this->values[] = $normalizedValues;
        }
    }

    public function getHeaders()
    {
        return [ $this->attributesHeaders ];
    }

    public function getRows(Type $var = null)
    {
        return $this->values;
    }

    public function toCSVFile($resource)
    {
        foreach ($this->getHeaders() as $header) {
            fputcsv($resource, $header, ";");
        }
        foreach ($this->getRows() as $row) {
            fputcsv($resource, $row, ";");
        }
    }
}
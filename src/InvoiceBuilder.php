<?php

namespace CloudFinance\EFattureWsClient;

require_once __DIR__ . "/../vendor/autoload.php";

use CloudFinance\EFattureWsClient\Exceptions\InvalidInvoice;
use CloudFinance\EFattureWsClient\Exceptions\InvalidInvoiceParameter;
use CloudFinance\EFattureWsClient\Iso3166;

class InvoiceBuilder
{
    private $domDocument;
    private $domXPath;
    private $header;
    private $body;

    public function __construct() {
        $template = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica versione="FPA12" xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
                xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">
                <FatturaElettronicaHeader></FatturaElettronicaHeader>
                <FatturaElettronicaBody></FatturaElettronicaBody>
            </p:FatturaElettronica>';

        $this->domDocument = \DOMDocument::loadXML($template);
        $this->domXPath = new \DOMXPath($this->domDocument);

        // $this->set("/p:FatturaElettronica/FatturaElettronicaHeader/fabio/luigi", "Hello");
        // $this->set("/p:FatturaElettronica/FatturaElettronicaHeader/fabio/p:luigi", "World");

        // $this->header = $this->domXPath->query('/p:FatturaElettronica/FatturaElettronicaHeader')->item(0);
        // $this->body = $this->domXPath->query('/p:FatturaElettronica/FatturaElettronicaBody')->item(0);
        // $this->header->nodeValue = $this->body->nodeValue = "fabio";
        // echo $this->domDocument->saveXML();
        // die();
    }

    public function validate()
    {
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

    public function set(string $path, $value)
    {
        $nodes = $this->domXPath->query($path);
        if (\count($nodes) === 1) {
            $nodes->item(0)->nodeValue = $value;
            return;
        }

        $parentNode = $this->domXPath->query('/p:FatturaElettronica')->item(0);
        $currentPath = '/p:FatturaElettronica';
        $tokens = \explode('/', $path);

        foreach ($tokens as $token) {
            if (empty($token)) {
                continue;
            }

            if ($token === 'p:FatturaElettronica') {
                continue;
            }

            $ts = \explode(":", $token);
            $tsExt = \count($ts) === 2;
            $namespace = $tsExt ? $ts[0] : "";
            $tag = $tsExt ? $ts[1] : $ts[0];

            $currentPath .= "/" . $token;
            $nodes = $this->domXPath->query($path);
            if (\count($nodes) > 0) {
                $node = $nodes->item(0);
            } else {
                $node = new \DOMElement($tag, "", $namespace);
                if ($tsExt) {
                    $node = $this->domDocument->createElementNS($this->domDocument->lookupNamespaceUri($namespace), $token);
                } else {
                    $node = $this->domDocument->createElement($token);
                }
                $parentNode->appendChild($node);
            }

            $parentNode = $node;
        }

        $parentNode->nodeValue = $value;
    }

    private function validateCountryCodeParam(string $value, string $paramName)
    {
        if (!Iso3166::isValidCountryCode($value)) {
            throw new InvalidInvoiceParameter("Parameter '$paramName' is not a valid ISO 3166-1 alpha-2 country code.");
        }
    }

    private function validateStringParam(string $value, string $paramName, int $maxLength, bool $empty)
    {
        if (!$empty) {
            if (empty($value)) {
                throw new InvalidInvoiceParameter("Parameter '$paramName' can't be empty.");
            }
        }

        if (\strlen($value) > $maxLength) {
            throw new InvalidInvoiceParameter("Parameter '$paramName' can't longer than $maxLength characters.");
        }
    }

    private function validateEnumParam(string $value, string $paramName, array $enum)
    {
        if (!in_array($value, $enum)) {
            $error = "Parameter '$paramName' value is not valid.";
            if (count($enum) < 5) {
                $error = \sprintf("Parameter '%s' value must be one of [%s].", $paramName, \implode(", ", $enum));
            }
            throw new InvalidInvoiceParameter($error);
        }
    }

    private function validateEmailParam(string $value, string $paramName, bool $empty)
    {
        if (empty($value)) {
            if (!$empty) {
                throw new InvalidInvoiceParameter("Parameter '$paramName' can't be empty.");
            } else {
                return;
            }
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidInvoiceParameter("Parameter '$paramName' is not a valid email.");
        }
    }

    public function setIdTrasmittente(string $idPaese, string $idCodice)
    {
        $idPaese = \strtoupper($idPaese);
        $this->validateCountryCodeParam($idPaese, "idPaese");
        $this->validateStringParam($idCodice, "idCodice", 28, false);

        $this->set("/p:FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdPaese", $idPaese);
        $this->set("/p:FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice", $idCodice);
    }

    public function setProgressivoInvio(string $progressivoInvio)
    {
        $this->validateStringParam($progressivoInvio, "progressivoInvio", 10, false);

        $this->set("/p:FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/ProgressivoInvio", $progressivoInvio);
    }

    public function setDestinatario(string $formatoTrasmissione, string $codiceDestinatario, string $pecDestinatario)
    {
        $enum = [
            "FPA12",
            "FPR12"
        ];
        $formatoTrasmissione = \strtoupper($formatoTrasmissione);
        $this->validateEnumParam($formatoTrasmissione, "formatoTrasmissione", $enum);

        if ($formatoTrasmissione === "FPA12") {
            $this->validateStringParam($codiceDestinatario, "codiceDestinatario", 6, false);
        } else {
            $this->validateStringParam($codiceDestinatario, "codiceDestinatario", 7, false);
        }

        if ($codiceDestinatario === "0000000") {
            $this->validateEmailParam($pecDestinatario, "pecDestinatario", false);
        }

        $this->set("/p:FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/FormatoTrasmissione", $formatoTrasmissione);
        $this->set("/p:FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/CodiceDestinatario", $codiceDestinatario);

        if ($codiceDestinatario === "0000000") {
            $this->set("/p:FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/PECDestinatario", $pecDestinatario);
        }
    }

    public function setContattiTrasmittente(string $telefono, string $email)
    {

    }

}

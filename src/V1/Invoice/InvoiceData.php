<?php

namespace CloudFinance\EFattureWsClient\V1\Invoice;

use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;
use CloudFinance\EFattureWsClient\Exceptions\InvalidInvoice;
use CloudFinance\EFattureWsClient\V1\Invoice\XmlWrapper;
use CloudFinance\EFattureWsClient\V1\Invoice\XmlWrapperValidators\SchemaValidator;
use CloudFinance\EFattureWsClient\V1\Invoice\XmlWrapperValidators\VFPR12Validator;


class InvoiceData extends XmlWrapper
{
    public const FATTURA_B2G = "FPA12";
    public const FATTURA_B2B = "FPR12";

    public static function create(string $formato)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" />';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xml);
        $instance = new self($domDocument);
        $instance->setFormatoTrasmissione($formato);
        $instance->setupValidators();
        return $instance;
    }

    public static function loadXML(string $xml)
    {
        $domDocument = new \DOMDocument();
        try {
            $domDocument->loadXML($xml, LIBXML_NOBLANKS | LIBXML_COMPACT);
        } catch (\Exception $ex) {
            $error = sprintf("Invalid invoice XML: %s.", $ex->getMessage());
            throw new InvalidInvoice($error, $ex->getCode());
        }
        $instance = new self($domDocument);
        $instance->setupValidators();
        return $instance;
    }

    private function setupValidators()
    {
        $this->addValidator(new SchemaValidator(__DIR__. "/../../../resources/Schema_VFPR12.xsd"));
        $this->addValidator(new VFPR12Validator());
    }

    public function setVersione(string $formato)
    {
        return $this->setFormatoTrasmissione($formato);
    }

    public function setFormatoTrasmissione(string $formato)
    {
        $formato = strtoupper($formato);
        if (($formato !== self::FATTURA_B2G) && ($formato !== self::FATTURA_B2B)) {
            throw new InvalidInvoice("Formato must be 'FPA12' or 'FPR12'.");
        }

        $attributes = $this->rootNode->attributes;
        $domAttribute = $attributes->getNamedItem('versione');

        if ($domAttribute === null) {
            $domAttribute = $this->domDocument->createAttribute('versione');
            $this->rootNode->appendChild($domAttribute);
        }

        $domAttribute->value = $formato;
        $this->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/FormatoTrasmissione", $formato);
        return $this;
    }

    public function generateFileName($suffix = "_")
    {
        $idPaese = $this->get("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdPaese");
        $idCodice = $this->get("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice");

        if (empty($idPaese)) {
            throw new EFattureWsClientException("Empty 'DatiTrasmissione/IdTrasmittente/IdPaese' field.");
        }

        if (empty($idCodice)) {
            throw new EFattureWsClientException("Empty 'DatiTrasmissione/IdTrasmittente/IdCodice' field.");
        }

        $fileName = \strtoupper($idPaese . $idCodice) . $suffix;
        return $fileName;
    }

    public function getTrasmittente()
    {
        $idPaese = $this->get("FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdPaese");
        $idCodice = $this->get("FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice");

        return $idPaese . $idCodice;
    }

    public function getProgressivoInvio()
    {
        $progressivoInvio = $this->get("FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/ProgressivoInvio");

        return $progressivoInvio;
    }

    public function getCedentePrestatore()
    {
        $idPaese = $this->get("FatturaElettronica/FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/IdFiscaleIVA/IdPaese");
        $idCodice = $this->get("FatturaElettronica/FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/IdFiscaleIVA/IdCodice");
        $codiceFiscale = $this->get("FatturaElettronica/FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale");

        return [
            "idPaese" => $idPaese,
            "idCodice" => $idCodice,
            "codiceFiscale" => $codiceFiscale,
        ];
    }

    public function getCessionarioCommittente()
    {
        $idPaese = $this->get("FatturaElettronica/FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/IdFiscaleIVA/IdPaese");
        $idCodice = $this->get("FatturaElettronica/FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/IdFiscaleIVA/IdCodice");
        $codiceFiscale = $this->get("FatturaElettronica/FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/CodiceFiscale");

        return [
            "idPaese" => $idPaese,
            "idCodice" => $idCodice,
            "codiceFiscale" => $codiceFiscale,
        ];
    }

    public function getFormatoTrasmissione()
    {
        $formatoTrasmissione = $this->get("FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/FormatoTrasmissione");

        return \strtoupper($formatoTrasmissione);
    }

    public function getVersione()
    {
        $attributes = $this->rootNode->attributes;
        $domAttribute = $attributes->getNamedItem('versione');
        if ($domAttribute === null) {
            return "";
        }

        return $domAttribute->value;
    }

    public function getCodiceDestinatario()
    {
        $codiceDestinatario = $this->get("FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/CodiceDestinatario");

        return $codiceDestinatario;
    }

    public function getPecDestinatario()
    {
        $pecDestinatario = $this->get("FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/PECDestinatario");

        return $pecDestinatario;
    }

}

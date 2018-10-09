<?php

namespace CloudFinance\EFattureWsClient\V1\Invoice;

use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;
use CloudFinance\EFattureWsClient\Exceptions\InvalidInvoice;
use CloudFinance\EFattureWsClient\Exceptions\InvalidXml;
use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapper;
use CloudFinance\EFattureWsClient\V1\Invoice\XmlWrapperValidators\VFPR12CommonValidator;
use CloudFinance\EFattureWsClient\V1\Invoice\XmlWrapperValidators\VFPR12DatesValidator;


class InvoiceData extends XmlWrapper
{
    const FATTURA_B2G = "FPA12";
    const FATTURA_B2B = "FPR12";

    public static function create($formato)
    {
        if (!is_string($formato)) {
            $givenType = (\is_object($formato)) ? get_class($formato) : gettype($formato);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" />';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xml);
        $instance = new self($domDocument);
        $instance->setFormatoTrasmissione($formato);
        $instance->setupValidators();
        return $instance;
    }

    public static function loadXML($xml)
    {
        if (!is_string($xml)) {
            $givenType = (\is_object($xml)) ? get_class($xml) : gettype($xml);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $domDocument = new \DOMDocument();
        try {
            $domDocument->loadXML($xml, LIBXML_NOBLANKS | LIBXML_COMPACT);
        } catch (\Exception $ex) {
            $error = sprintf("Invalid invoice XML: %s.", $ex->getMessage());
            throw new InvalidXml($error, $ex->getCode());
        }
        $instance = new self($domDocument);
        $instance->setupValidators();
        return $instance;
    }

    /**
     * Ristruttura il tag <FatturaElettronicaHeader>.
     *
     * @return void
     */
    public function recreateHeader()
    {
        // Stacca la testa e sostituiscila con una completa di tutti gli elementi

        // Questi sono tutti i tag di <DatiTrasmissione>.
        $DatiTrasmissioneValues = [
            "/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdPaese" => "",
            "/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice" => "",
            "/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/ProgressivoInvio" => "",
            "/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/FormatoTrasmissione" => "",
            "/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/CodiceDestinatario" => "",
            "/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/ContattiTrasmittente/Telefono" => "",
            "/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/ContattiTrasmittente/Email" => "",
            "/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/PECDestinatario" => ""
        ];

        foreach ($DatiTrasmissioneValues as $path => $v) {
            $value = $this->get($path);

            if (empty($value)) {
                continue;
            }

            $DatiTrasmissioneValues[$path] = $value;
        }

        // Questi sono i nodi di <FatturaElettronicaHeader> che vanno preservati
        // (se presenti) e aggiunti in coda a <DatiTrasmissione>.
        $FatturaElettronicaHeaderOldNodes = [
            "/FatturaElettronica/FatturaElettronicaHeader/CedentePrestatore" => null,
            "/FatturaElettronica/FatturaElettronicaHeader/RappresentanteFiscale" => null,
            "/FatturaElettronica/FatturaElettronicaHeader/CessionarioCommittente" => null,
            "/FatturaElettronica/FatturaElettronicaHeader/TerzoIntermediarioOSoggettoEmittente" => null,
            "/FatturaElettronica/FatturaElettronicaHeader/SoggettoEmittente" => null
        ];
        foreach ($FatturaElettronicaHeaderOldNodes as $path => $n) {
            $node = $this->retrieveNode($path);
            $FatturaElettronicaHeaderOldNodes[$path] = $node;
        }

        $FatturaElettronicaHeaderOldNode = $this->retrieveNode("/FatturaElettronica/FatturaElettronicaHeader");
        $FatturaElettronicaHeaderNewNode = $this->domDocument->createElement("FatturaElettronicaHeader");
        $res = $this->rootNode->replaceChild($FatturaElettronicaHeaderNewNode, $FatturaElettronicaHeaderOldNode);
        if ($res === false) {
            throw new InvalidInvoice("Unable to replace header");
        }

        foreach ($DatiTrasmissioneValues as $path => $value) {
            $this->set($path, $value);
        }

        foreach ($FatturaElettronicaHeaderOldNodes as $path => $node) {
            if ($node === null) {
                continue;
            }
            $FatturaElettronicaHeaderNewNode->appendChild($node);
        }
    }

    public function normalize()
    {
        $this->recreateHeader();
        parent::normalize();
    }

    private function setupValidators()
    {
        $this->addValidator(new VFPR12CommonValidator());
        $this->addValidator(new VFPR12DatesValidator());
    }

    public function setVersione($formato)
    {
        return $this->setFormatoTrasmissione($formato);
    }

    public function setFormatoTrasmissione($formato)
    {
        if (!is_string($formato)) {
            $givenType = (\is_object($formato)) ? get_class($formato) : gettype($formato);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $formato = strtoupper($formato);
        if (($formato !== self::FATTURA_B2G) && ($formato !== self::FATTURA_B2B)) {
            throw new InvalidInvoice("Formato must be 'FPA12' or 'FPR12'.");
        }

        $this->setAttribute("/FatturaElettronica", "versione", $formato);
        $this->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/FormatoTrasmissione", $formato);
        return $this;
    }

    public function generateFileName($suffix = "")
    {
        if (!is_string($suffix)) {
            $givenType = (\is_object($suffix)) ? get_class($suffix) : gettype($suffix);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

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

        if (empty($idPaese) || empty($idCodice)) {
            return null;
        }

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
        return $this->getAttribute("/FatturaElettronica", "versione");
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

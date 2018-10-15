<?php

namespace CloudFinance\EFattureWsClient\V1\Invoice;

use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;
use CloudFinance\EFattureWsClient\Exceptions\InvalidInvoice;
use CloudFinance\EFattureWsClient\Exceptions\InvalidXml;
use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapper;
use CloudFinance\EFattureWsClient\V1\Invoice\FPR12Map;
use CloudFinance\EFattureWsClient\V1\Invoice\XmlWrapperValidators\VFPR12CommonValidator;
use CloudFinance\EFattureWsClient\V1\Invoice\XmlWrapperValidators\VFPR12DatesValidator;

class InvoiceData extends XmlWrapper
{
    const FATTURA_B2G = "FPA12";
    const FATTURA_B2B = "FPR12";

    protected $data = [];

    public static function create()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" />';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xml);
        $instance = new self($domDocument);
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
    public function orderTags()
    {
        // Valori nodi attualmente presenti nel documento
        $data = [];
        $leafs = $this->domXPath->query("//*[not(*)]");
        for ($i = 0; $i < $leafs->length; $i++) {
            $leaf = $leafs->item($i);
            $leafPath = $leaf->getNodePath();
            $leafPath = preg_replace('/[^\/]*:/', '', $leafPath);
            $leafValue = $this->get($leafPath);

            if (empty($leafValue)) {
                continue;
            }

            $data[$leafPath] = $this->get($leafPath);
        }

        // Pulisci il documento attuale
        $this->domDocument->documentElement->nodeValue = "";
        while ($this->domDocument->documentElement->childNodes->length > 0) {
            $this->domDocument->documentElement->removeChild($this->domDocument->documentElement->childNodes->item(0));
        }

        // Rigenera il documento secondo l'ordinamento definito in $map
        $map = FPR12Map::get();
        $leafsPaths = array_keys($data);
        foreach ($map as $mapPath) {
            $regexPath = $mapPath;
            $regexPath = str_replace('[n]', '', $mapPath);
            $regexPath = str_replace('/', '(\[.*\])?\/', $regexPath);
            $regexPath = '/.?' . $regexPath . '/';

            $matches  = preg_grep($regexPath, $leafsPaths);

            foreach ($matches as $match) {
                parent::set($match, $data[$match]);
            }
        }
    }

    public function normalize()
    {
        $this->orderTags();
        // parent::normalize();
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
        parent::set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/FormatoTrasmissione", $formato);
        return $this;
    }

    public function set($path, $value)
    {
        if (preg_match('/FormatoTrasmissione$/', $path)) {
            $this->setFormatoTrasmissione($value);
            return;
        }

        parent::set($path, $value);
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

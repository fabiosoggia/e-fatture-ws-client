<?php

namespace CloudFinance\EFattureWsClient\V1\LiquidazionePeriodica;

use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;
use CloudFinance\EFattureWsClient\Exceptions\InvalidXml;
use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapper;
use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapperValidators\SchemaValidator;
use DateTime;
use DOMDocument;

class LiquidazionePeriodicaTrimestrale extends XmlWrapper
{

    public function __construct(DOMDocument $domDocument) {
        parent::__construct($domDocument);
        // $this->addValidator(new SchemaValidator(__DIR__ . "/../../../resources/Specifiche IVP2018_SchemaIV18/comunicazioneIvp_2018_v1.xsd"));
    }

    public static function create()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <Fornitura xmlns="urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" />';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xml);
        $instance = new self($domDocument);
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

        if (strpos($xml, 'xmlns="urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp"') === false &&
            strpos($xml, 'xmlns:iv="urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp"') === false) {
            throw new InvalidXml("Invalid XML: not supported 'xmlns:iv' namespace.", 0);
        }

        $xml = str_replace('xmlns="urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp"', '', $xml);
        $xml = str_replace('xmlns:iv="urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp"', '', $xml);
        $xml = str_replace(['<iv:', '</iv:'], ['<', '</'], $xml);
        $domDocument = new \DOMDocument();
        try {
            $domDocument->loadXML($xml, LIBXML_NOBLANKS | LIBXML_COMPACT | LIBXML_NOWARNING | LIBXML_NOERROR);
        } catch (\Exception $ex) {
            $error = sprintf("Invalid XML: %s.", $ex->getMessage());
            throw new InvalidXml($error, $ex->getCode());
        }
        $domDocument->documentElement->setAttribute("xmlns", "urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp");
        $instance = new self($domDocument);
        return $instance;
    }

    public function getErrors()
    {
        $this->addValidator(new SchemaValidator(__DIR__ . "/../../../resources/Specifiche IVP2018_SchemaIV18/comunicazioneIvp_2018_v1.xsd"));
        return parent::getErrors();
    }

    public function generateFileName($suffix = "")
    {
        if (!is_string($suffix)) {
            $givenType = (\is_object($suffix)) ? get_class($suffix) : gettype($suffix);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $idCodice = $this->get("/Comunicazione/Frontespizio/CodiceFiscale");

        if ($idCodice === null) {
            throw new EFattureWsClientException("Empty 'Comunicazione/Frontespizio/CodiceFiscale' field.");
        }

        $fileName = "IT" . \strtoupper($idCodice) . "_LI" . $suffix;
        return $fileName;
    }

    /**
     * Come getFingerprint() ma non tinene conto degli allegati presenti nel
     * file XML.
     *
     * @return string
     */
    public function getSoftFingerprint()
    {
        $dom = clone $this->getDomDocument();
        $xml = $dom->saveXML();
        $xml = \strtolower($xml);
        $fingerprint = \md5($xml);
        return $fingerprint;
    }

    public function setFloat(string $path, float $value)
    {
        $value = \number_format($value, 2, ',', '');
        $this->set($path, $value);
    }

    public function setBool(string $path, bool $value)
    {
        $value = intval($value) . '';
        $this->set($path, $value);
    }

    public function setDate(string $path, DateTime $value)
    {
        $value = $value->format("dmY");
        $this->set($path, $value);
    }
}

<?php

namespace CloudFinance\EFattureWsClient\V1\Invoice;

use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;
use CloudFinance\EFattureWsClient\Exceptions\InvalidInvoice;
use CloudFinance\EFattureWsClient\Exceptions\InvalidXml;
use CloudFinance\EFattureWsClient\V1\Invoice\FPR12Map;
use CloudFinance\EFattureWsClient\V1\Invoice\FSM10Map;
use CloudFinance\EFattureWsClient\V1\Invoice\XmlWrapperValidators\VFPR12CommonValidator;
use CloudFinance\EFattureWsClient\V1\Invoice\XmlWrapperValidators\VFPR12DatesValidator;
use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapper;
use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapperValidators\SchemaValidator;

class InvoiceData extends XmlWrapper
{
    const FATTURA_B2G = "FPA12";
    const FATTURA_B2B = "FPR12";
    const FATTURA_FSM = "FSM10";

    public static function create()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" />';
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xml);
        $instance = new self($domDocument);
        return $instance;
    }

    public static function createFSM10()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronicaSemplificata xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.0" />';
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

        $domDocument = new \DOMDocument();
        try {
            $domDocument->loadXML($xml, LIBXML_NOBLANKS | LIBXML_COMPACT);
        } catch (\Exception $ex) {
            $error = sprintf("Invalid invoice XML: %s.", $ex->getMessage());
            throw new InvalidXml($error, $ex->getCode());
        }
        $instance = new self($domDocument);
        return $instance;
    }

    /**
     * Ristruttura il tag <FatturaElettronicaHeader>.
     *
     * @return void
     */
    public function orderTags()
    {
        $formato = $this->getFormatoTrasmissione();

        // Valori nodi attualmente presenti nel documento
        $data = $this->toArray();

        // Pulisci il documento attuale
        $this->domDocument->documentElement->nodeValue = "";
        while ($this->domDocument->documentElement->childNodes->length > 0) {
            $this->domDocument->documentElement->removeChild($this->domDocument->documentElement->childNodes->item(0));
        }

        // Rigenera il documento secondo l'ordinamento definito in $map
        $map = ($formato === self::FATTURA_FSM) ? FSM10Map::get() : FPR12Map::get();
        $leafsPaths = array_keys($data);
        foreach ($map as $mapPath) {
            $regexPath = $mapPath;
            $regexPath = str_replace('[n]', '', $mapPath);
            $regexPath = str_replace('/', '(\[.*\])?\/', $regexPath);
            $regexPath = '/.?' . $regexPath . '/';

            $matches  = preg_grep($regexPath, $leafsPaths);

            foreach ($matches as $match) {
                $value = $data[$match];
                if (empty($value)) {
                    continue;
                }

                parent::set($match, $data[$match]);
            }
        }
    }

    /**
     * Rimuove i tag vuoti dall'XML e riorganizzali secondo la sequenza definita
     * dallo schema.
     *
     * @return this
     */
    public function normalize()
    {
        $this->orderTags();
        return $this;
        // parent::normalize();
    }

    public function getErrors()
    {
        $formato = $this->getFormatoTrasmissione();
        $this->validators = [];

        if ($formato === self::FATTURA_FSM) {
            $this->addValidator(new SchemaValidator(__DIR__ . "/../../../resources/Schema_VFSM10.xsd"));
        } else {
            $this->addValidator(new VFPR12CommonValidator());
            // $this->addValidator(new VFPR12DatesValidator());
        }

        return parent::getErrors();
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
        if (!(in_array($formato, [ self::FATTURA_B2B, self::FATTURA_B2G, self::FATTURA_FSM ]))) {
            throw new InvalidInvoice("Formato must be 'FPA12', 'FPR12' o 'FSM10'.");
        }

        $rootNodeTag = $this->getRootNodeTag();
        $this->setAttribute("/$rootNodeTag", "versione", $formato);
        parent::set("/FatturaElettronicaHeader/DatiTrasmissione/FormatoTrasmissione", $formato);
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

        $idPaese = $this->get("/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdPaese");
        $idCodice = $this->get("/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice");

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
        $idPaese = $this->get("/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdPaese");
        $idCodice = $this->get("/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice");

        if (empty($idPaese) || empty($idCodice)) {
            return null;
        }

        return $idPaese . $idCodice;
    }

    public function getProgressivoInvio()
    {
        $progressivoInvio = $this->get("/FatturaElettronicaHeader/DatiTrasmissione/ProgressivoInvio");

        return $progressivoInvio;
    }

    public function getCedentePrestatore()
    {
        $idPaese = $this->get("/FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/IdFiscaleIVA/IdPaese");
        $idCodice = $this->get("/FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/IdFiscaleIVA/IdCodice");
        $codiceFiscale = $this->get("/FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale");

        if ($this->getFormatoTrasmissione() === self::FATTURA_FSM) {
            $idPaese = $this->get("/FatturaElettronicaHeader/CedentePrestatore/IdFiscaleIVA/IdPaese");
            $idCodice = $this->get("/FatturaElettronicaHeader/CedentePrestatore/IdFiscaleIVA/IdCodice");
            $codiceFiscale = $this->get("/FatturaElettronicaHeader/CedentePrestatore/CodiceFiscale");
        }

        return [
            "idPaese" => $idPaese,
            "idCodice" => $idCodice,
            "codiceFiscale" => $codiceFiscale,
        ];
    }

    public function getCessionarioCommittente()
    {
        $idPaese = $this->get("/FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/IdFiscaleIVA/IdPaese");
        $idCodice = $this->get("/FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/IdFiscaleIVA/IdCodice");
        $codiceFiscale = $this->get("/FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/CodiceFiscale");

        if ($this->getFormatoTrasmissione() === self::FATTURA_FSM) {
            $idPaese = $this->get("/FatturaElettronicaHeader/CessionarioCommittente/IdentificativiFiscali/IdFiscaleIVA/IdPaese");
            $idCodice = $this->get("/FatturaElettronicaHeader/CessionarioCommittente/IdentificativiFiscali/IdFiscaleIVA/IdCodice");
            $codiceFiscale = $this->get("/FatturaElettronicaHeader/CessionarioCommittente/IdentificativiFiscali/CodiceFiscale");
        }

        return [
            "idPaese" => $idPaese,
            "idCodice" => $idCodice,
            "codiceFiscale" => $codiceFiscale,
        ];
    }

    public function getFormatoTrasmissione()
    {
        $formatoTrasmissione = $this->get("/FatturaElettronicaHeader/DatiTrasmissione/FormatoTrasmissione");

        return \strtoupper($formatoTrasmissione);
    }

    public function getVersione()
    {
        $rootNodeTag = $this->getRootNodeTag();
        return $this->getAttribute("/$rootNodeTag", "versione");
    }

    public function getCodiceDestinatario()
    {
        $codiceDestinatario = $this->get("/FatturaElettronicaHeader/DatiTrasmissione/CodiceDestinatario");

        return $codiceDestinatario;
    }

    public function getPecDestinatario()
    {
        $pecDestinatario = $this->get("/FatturaElettronicaHeader/DatiTrasmissione/PECDestinatario");

        return $pecDestinatario;
    }

    public function toHtml()
    {
        $formato = $this->getFormatoTrasmissione();

        $xslPath = __DIR__ . "/../../../resources/fatturaordinaria_v1.2.1.xsl";
        if ($formato === self::FATTURA_B2G) {
            $xslPath = __DIR__ . "/../../../resources/fatturaPA_v1.2.1.xsl";
        }
        $xsl = new \DOMDocument();
        $xsl->load($xslPath);

        $proc = new \XSLTProcessor();
        $proc->importStyleSheet($xsl);

        return $proc->transformToXML($this->domDocument);
    }

    public function applyXsl($xslPath)
    {
        $xsl = new \DOMDocument();
        $xsl->load($xslPath);

        $proc = new \XSLTProcessor();
        $proc->importStyleSheet($xsl);

        return $proc->transformToXML($this->domDocument);
    }

}

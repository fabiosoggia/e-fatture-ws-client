<?php

namespace CloudFinance\EFattureWsClient\V1\LiquidazionePeriodica;

use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;
use CloudFinance\EFattureWsClient\Exceptions\InvalidXml;
use CloudFinance\EFattureWsClient\V1\LiquidazionePeriodica\XmlWrapperValidators\IVP18CommonValidator;
use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapper;
use DateTime;
use DOMDocument;

/**
 * Comunicazione IVA periodica con prospetto di liquidazione (LIPE), codice
 * fornitura IVP18.
 *
 * @see freeinvoice-ws/src/SdI/Standard/LiquidazionePeriodica/README.md
 */
class LiquidazionePeriodicaTrimestrale extends XmlWrapper
{
    /**
     * Tipologia file da usare nel nome del file e nel campo 'TipoFile' della
     * chiamata 'Trasmetti' al Sistema Ricevente.
     */
    const TIPO_FILE = "LI";

    /**
     * Codice fornitura della comunicazione.
     */
    const CODICE_FORNITURA = "IVP18";

    public function __construct(DOMDocument $domDocument) {
        parent::__construct($domDocument);
        $this->addValidator(new IVP18CommonValidator());
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

    /**
     * Nome del file da trasmettere al Sistema Ricevente, senza estensione.
     *
     * La nomenclatura è quella del paragrafo 2.2 dell'allegato "Modalità di
     * trasmissione dati" al provvedimento 58793 del 27/03/2017:
     *
     *     {codicePaese}{identificativoFiscale}_LI_{progressivo}
     *
     * Il progressivo va passato in $suffix già preceduto dall'underscore, come
     * per InvoiceData::generateFileName(); è una stringa alfanumerica di
     * lunghezza massima 5 caratteri il cui unico scopo è rendere il nome file
     * diverso da ogni altro nome già trasmesso dallo stesso soggetto.
     *
     * @param string $suffix progressivo, nella forma "_00001"
     * @param string|null $identificativoFiscale identificativo del soggetto
     *      trasmittente; se omesso si usa il codice fiscale del soggetto a cui
     *      la comunicazione si riferisce
     * @return string
     */
    public function generateFileName($suffix = "", $identificativoFiscale = null)
    {
        if (!is_string($suffix)) {
            $givenType = (\is_object($suffix)) ? get_class($suffix) : gettype($suffix);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $idCodice = ($identificativoFiscale === null)
            ? $this->getCodiceFiscale()
            : $identificativoFiscale;

        if (empty($idCodice)) {
            throw new EFattureWsClientException("Empty 'Comunicazione/Frontespizio/CodiceFiscale' field.");
        }

        $fileName = "IT" . \strtoupper($idCodice) . "_" . self::TIPO_FILE . $suffix;
        return $fileName;
    }

    /**
     * Codice fiscale del soggetto a cui la comunicazione si riferisce.
     *
     * @return string|null
     */
    public function getCodiceFiscale()
    {
        return $this->get("/Comunicazione/Frontespizio/CodiceFiscale");
    }

    /**
     * Partita IVA del soggetto a cui la comunicazione si riferisce.
     *
     * @return string|null
     */
    public function getPartitaIva()
    {
        return $this->get("/Comunicazione/Frontespizio/PartitaIVA");
    }

    /**
     * Codice fiscale del soggetto che trasmette la fornitura.
     *
     * @return string|null
     */
    public function getCodiceFiscaleDichiarante()
    {
        return $this->get("/Intestazione/CodiceFiscaleDichiarante");
    }

    /**
     * Anno d'imposta, nel formato aaaa.
     *
     * @return string|null
     */
    public function getAnnoImposta()
    {
        return $this->get("/Comunicazione/Frontespizio/AnnoImposta");
    }

    /**
     * Codice fornitura, che per la LIPE vale sempre 'IVP18'.
     *
     * @return string|null
     */
    public function getCodiceFornitura()
    {
        return $this->get("/Intestazione/CodiceFornitura");
    }

    /**
     * Identificativo della comunicazione all'interno della fornitura: stringa
     * di 5 cifre non tutte a zero, obbligatoria per lo schema.
     *
     * @return string|null
     */
    public function getIdentificativo()
    {
        return $this->getAttribute("/Comunicazione", "identificativo");
    }

    /**
     * @param string $identificativo 5 cifre, non tutte a zero
     * @return void
     */
    public function setIdentificativo($identificativo)
    {
        $this->setAttribute("/Comunicazione", "identificativo", (string) $identificativo);
    }

    /**
     * Numero di moduli presenti nel prospetto di liquidazione: uno per ogni
     * mese o trimestre comunicato, al massimo 5.
     *
     * @return int
     */
    public function countModuli()
    {
        return $this->count("/Comunicazione/DatiContabili/Modulo");
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

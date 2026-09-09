<?php

namespace CloudFinance\EFattureWsClient\V1\LiquidazionePeriodica\XmlWrapperValidators;

use CloudFinance\EFattureWsClient\V1\Enum\ErrorCodes;
use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapper;
use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapperValidator;

/**
 * Controlli sulla Comunicazione IVA periodica con prospetto di liquidazione
 * (codice fornitura IVP18).
 *
 * Le regole di compilazione dei singoli campi sono tutte espresse nello schema
 * XSD, che è quindi il controllo principale; qui si aggiunge solo il limite di
 * dimensione, che invece è un vincolo del canale di trasmissione e non dello
 * schema.
 *
 * @see freeinvoice-ws/src/SdI/Standard/LiquidazionePeriodica/docs/SpecificheIVP18_2024.pdf
 * @see freeinvoice-ws/src/SdI/Standard/LiquidazionePeriodica/docs/Modalita_di_trasmissione_dati_v1.0.pdf
 */
class IVP18CommonValidator implements XmlWrapperValidator
{
    /**
     * Dimensione massima del file allegato al messaggio SOAP: 5 megabytes
     * ("Modalità di trasmissione dati" §3.1). Il valore tiene lo stesso
     * margine già usato per le fatture in Client::uploadInvoice(), perché la
     * firma CAdES aggiunta successivamente fa crescere il file.
     */
    const MAX_FILE_SIZE = 4718592;

    /**
     * Percorso dello schema della fornitura.
     *
     * Si valida contro 'fornituraIvp_2018_v1.xsd' perché è l'unico dei quattro
     * schemi a dichiarare l'elemento radice 'Fornitura'; gli altri definiscono
     * solo tipi ('Comunicazione_IVP_Type', 'Intestazione_IVP_Type') e non sono
     * utilizzabili come schema di validazione del documento completo.
     *
     * Nota: nelle copie in resources/ i due 'xs:import' di 'fornitura_v3.xsd'
     * sono stati corretti da '../../common/fornitura_v3.xsd' al nome semplice,
     * perché l'archivio pubblicato dall'Agenzia non riproduce quel layout di
     * cartelle. Le copie non modificate sono in
     * freeinvoice-ws/src/SdI/Standard/LiquidazionePeriodica/SchemaIVP18/.
     */
    private function getSchemaLocation()
    {
        return __DIR__ . "/../../../../resources/Specifiche IVP2018_SchemaIV18/fornituraIvp_2018_v1.xsd";
    }

    public function getErrors(XmlWrapper $xmlWrapper)
    {
        $xml = $xmlWrapper->saveXML();

        if (\strlen($xml) > self::MAX_FILE_SIZE) {
            return [ ErrorCodes::IVP18_00003 => ErrorCodes::IVP18_00003_MSG ];
        }

        $schemaErrors = $this->getSchemaErrors($xml);
        if (empty($schemaErrors)) {
            return [];
        }

        $messages = \implode("\n", $schemaErrors);
        return [ ErrorCodes::IVP18_00002 => ErrorCodes::IVP18_00002_MSG . ":\n" . $messages ];
    }

    /**
     * Valida l'XML serializzato contro lo schema della fornitura.
     *
     * Si valida la stringa e non il DOMDocument di $xmlWrapper perché
     * LiquidazionePeriodicaTrimestrale::loadXML() rimuove la dichiarazione di
     * namespace dal documento e la reimposta con DOMElement::setAttribute(),
     * che crea un attributo normale e non una vera dichiarazione: gli elementi
     * del documento in memoria risultano quindi privi di namespace (questo
     * serve a XmlWrapper, che interroga i path senza prefisso). Rileggendo la
     * serializzazione l'attributo 'xmlns' torna a essere una dichiarazione
     * valida.
     *
     * Come effetto collaterale si valida esattamente il byte stream che verrà
     * trasmesso all'Agenzia delle Entrate.
     *
     * @param string $xml
     * @return array mappa codice libxml => messaggio
     */
    private function getSchemaErrors($xml)
    {
        $internalErrorPreviousValue = \libxml_use_internal_errors(true);
        \libxml_clear_errors();

        $nativeErrors = [];
        $domDocument = new \DOMDocument();
        if (!$domDocument->loadXML($xml)) {
            $nativeErrors = \libxml_get_errors();
        } elseif (!$domDocument->schemaValidate($this->getSchemaLocation())) {
            $nativeErrors = \libxml_get_errors();
        }

        \libxml_clear_errors();
        \libxml_use_internal_errors($internalErrorPreviousValue);

        $errors = [];
        foreach ($nativeErrors as $nativeError) {
            $errors[$nativeError->code] = $nativeError->message;
        }
        return $errors;
    }
}

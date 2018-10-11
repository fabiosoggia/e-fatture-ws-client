<?php

namespace CloudFinance\EFattureWsClient\V1\Invoice;

use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;
use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapper;
use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapperValidators\SchemaValidator;

class NotificaEsito extends XmlWrapper
{
    public static function create()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <types:NotificaEsitoCommittente
                xmlns:types="http://www.fatturapa.gov.it/sdi/messaggi/v1.0"
                xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                versione="1.0"
                xsi:schemaLocation="http://www.fatturapa.gov.it/sdi/messaggi/v1.0 MessaggiTypes_v1.0.xsd ">
                <IdentificativoSdI>111</IdentificativoSdI>
                <RiferimentoFattura>
                    <NumeroFattura></NumeroFattura>
                    <AnnoFattura></AnnoFattura>
                    <PosizioneFattura></PosizioneFattura>
                </RiferimentoFattura>
                <Esito></Esito>
                <Descrizione></Descrizione>
                <MessageIdCommittente>000000</MessageIdCommittente>
            </types:NotificaEsitoCommittente>';
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
            throw new InvalidInvoice($error, $ex->getCode());
        }
        $instance = new self($domDocument);
        $instance->setupValidators();
        return $instance;
    }

    private function setupValidators()
    {
        $this->addValidator(new SchemaValidator(__DIR__. "/../../../resources/MessaggiTypes_v1.1.xsd"));
    }

    public function setIdentificativoSdi($indentificativoId)
    {
        if (!is_string($indentificativoId)) {
            $givenType = (\is_object($indentificativoId)) ? get_class($indentificativoId) : gettype($indentificativoId);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $this->set("/NotificaEsitoCommittente/IdentificativoSdI", $indentificativoId);
    }

    public function setRiferimentoFattura($numeroFattura, $annoFattura, $posizioneFattura)
    {
        if (!is_string($numeroFattura)) {
            $givenType = (\is_object($numeroFattura)) ? get_class($numeroFattura) : gettype($numeroFattura);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        if (!is_int($annoFattura)) {
            $givenType = (\is_object($annoFattura)) ? get_class($annoFattura) : gettype($annoFattura);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 2, __METHOD__, "int", $givenType);
            throw new \InvalidArgumentException($message);
        }
        if (!is_int($posizioneFattura)) {
            $givenType = (\is_object($posizioneFattura)) ? get_class($posizioneFattura) : gettype($posizioneFattura);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 3, __METHOD__, "int", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $this->set("/NotificaEsitoCommittente/RiferimentoFattura/NumeroFattura", $numeroFattura);
        $this->set("/NotificaEsitoCommittente/RiferimentoFattura/AnnoFattura", $annoFattura);
        $this->set("/NotificaEsitoCommittente/RiferimentoFattura/PosizioneFattura", $posizioneFattura);
    }

    public function setEsito($esito)
    {
        if (!is_string($esito)) {
            $givenType = (\is_object($esito)) ? get_class($esito) : gettype($esito);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        $this->set("/NotificaEsitoCommittente/Esito", $esito);
    }

    public function setDescrizione($descrizione)
    {
        if (!is_string($descrizione)) {
            $givenType = (\is_object($descrizione)) ? get_class($descrizione) : gettype($descrizione);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        $this->set("/NotificaEsitoCommittente/Descrizione", $descrizione);
    }

    public function setMessageIdCommittente($messageIdCommittente)
    {
        if (!is_string($messageIdCommittente)) {
            $givenType = (\is_object($messageIdCommittente)) ? get_class($messageIdCommittente) : gettype($messageIdCommittente);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        $this->set("/NotificaEsitoCommittente/MessageIdCommittente", $messageIdCommittente);
    }
}

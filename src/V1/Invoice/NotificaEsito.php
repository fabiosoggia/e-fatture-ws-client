<?php

namespace CloudFinance\EFattureWsClient\V1\Invoice;

use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;
use CloudFinance\EFattureWsClient\V1\Invoice\XmlWrapper;
use CloudFinance\EFattureWsClient\V1\Invoice\XmlWrapperValidators\SchemaValidator;

class NotificaEsito extends XmlWrapper
{
    public static function create(string $formato)
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
        $this->addValidator(new SchemaValidator(__DIR__. "/../../../resources/MessaggiFatturaTypes_v1.0.xsd"));
    }

    public function setIdentificativoSdi(string $indentificativoId)
    {
        $this->set("/NotificaEsitoCommittente/IdentificativoSdI", $indentificativoId);
    }

    public function setRiferimentoFattura(string $numeroFattura, int $annoFattura, int $posizioneFattura)
    {
        $this->set("/NotificaEsitoCommittente/RiferimentoFattura/NumeroFattura", $numeroFattura);
        $this->set("/NotificaEsitoCommittente/RiferimentoFattura/AnnoFattura", $annoFattura);
        $this->set("/NotificaEsitoCommittente/RiferimentoFattura/PosizioneFattura", $posizioneFattura);
    }

    public function setEsito(string $esito)
    {
        $this->set("/NotificaEsitoCommittente/Esito", $esito);
    }

    public function setDescrizione(string $descrizione)
    {
        $this->set("/NotificaEsitoCommittente/Descrizione", $descrizione);
    }

    public function setMessageIdCommittente(string $messageIdCommittente)
    {
        $this->set("/NotificaEsitoCommittente/MessageIdCommittente", $messageIdCommittente);
    }
}

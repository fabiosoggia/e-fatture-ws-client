<?php

namespace CloudFinance\EFattureWsClient\Tests;

use PHPUnit\Framework\TestCase;
use CloudFinance\EFattureWsClient\V1\Invoice\InvoiceData;

class InvoiceDataTest extends TestCase
{
    public function testNormalizeXML()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test 01</IdCodice>
                </IdTrasmittente>
                <FormatoTrasmissione>FPR12</FormatoTrasmissione>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>';
        $builder = InvoiceData::loadXML($xml);
        $this->assertXmlStringEqualsXmlString($builder->saveXML(true), '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test 01</IdCodice>
                </IdTrasmittente>
                <FormatoTrasmissione>FPR12</FormatoTrasmissione>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>', "La normalizzazione ha apportato cambiamenti non previsti.");

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente />
                <FormatoTrasmissione>FPR12</FormatoTrasmissione>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>';
        $builder = InvoiceData::loadXML($xml);
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">
                <FatturaElettronicaHeader>
                    <DatiTrasmissione>
                    <FormatoTrasmissione>FPR12</FormatoTrasmissione>
                    </DatiTrasmissione>
                </FatturaElettronicaHeader>
            </p:FatturaElettronica>',
            $builder->saveXML(true),
            "La normalizzazione non ha rimosso gli attributi dei tag senza contenuto.");
    }

    public function testSet()
    {
        // $xml = file_get_contents("C:\\xampp\\htdocs\\eFATTURE-ws\\logs\\IT07945211006_1S2TQ.xml");
        // $xml = "";
        $builder = InvoiceData::create(InvoiceData::FATTURA_B2B);
        $builder->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice", "Test 01");

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <FormatoTrasmissione>FPR12</FormatoTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test 01</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>',
            $builder->saveXML(true),
            "Il metodo set() non ha settato il tag /FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice.");



        $builder->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice", "Test 02");
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <FormatoTrasmissione>FPR12</FormatoTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test 02</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>',
            $builder->saveXML(true),
            "Il metodo set() non ha modificato il tag /FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice");

        $builder->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice", "");
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <FormatoTrasmissione>FPR12</FormatoTrasmissione>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>',
            $builder->saveXML(true),
            "Il metodo set() non ha eliminato il tag /FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice.");

        $builder = InvoiceData::create(InvoiceData::FATTURA_B2B);
        $builder->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice[1]", "Test 04.1");
        $builder->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice[2]", "Test 04.2");
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <FormatoTrasmissione>FPR12</FormatoTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test 04.1</IdCodice>
                    <IdCodice>Test 04.2</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>',
            $builder->saveXML(true),
            "Il metodo set() non ha settato il tag /FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice con indice [1] e [2].");
    }

    public function testGet()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>';
        $builder = InvoiceData::loadXML($xml);

        $result = $builder->get("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice");
        $this->assertSame($result, "Test", "Il metodo get() non ha restituito il valore corretto.");


        $result = $builder->get("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice[2]");
        $this->assertSame($result, null, "Il metodo get() non ha restituito null.");


        $result = $builder->get("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice[2]", "default");
        $this->assertSame($result, "default", "Il metodo get() non ha restituito 'default'.");
    }

    public function testHas()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdPaese></IdPaese>
                    <IdCodice>Test</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>';
        $builder = InvoiceData::loadXML($xml);

        $result = $builder->has("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice");
        $this->assertTrue($result, "Il metodo has() non ha restituito il true.");

        $result = $builder->has("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice[1]");
        $this->assertTrue($result, "Il metodo has() non ha restituito il true [1].");

        $result = $builder->has("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice[2]");
        $this->assertFalse($result, "Il metodo has() non ha restituito il true [2].");

        $result = $builder->has("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdPaese");
        $this->assertFalse($result, "Il metodo has() ha restituito true per un nodo vuoto.");
    }

    public function testGetFingerprint()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdPaese>IT</IdPaese>
                    <IdCodice>000000</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>';
        $invoice = InvoiceData::loadXML($xml);
        $this->assertEquals("723346d11997b975ffea2273eb99dadd", $invoice->getFingerprint(), "Il metodo getFingerprint() ha generato una fingerprint diversa.");

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdCodice>000000</IdCodice>
                    <IdPaese>IT</IdPaese>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>';
        $invoice = InvoiceData::loadXML($xml);
        $this->assertEquals("723346d11997b975ffea2273eb99dadd", $invoice->getFingerprint(), "Il metodo getFingerprint() non deve tener conto della posizione locale dei tag.");

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdCodice>000000</IdCodice>
                    <IdPaese>IT</IdPaese>
                </IdTrasmittente>
                <ProgressivoInvio></ProgressivoInvio>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>';
        $invoice = InvoiceData::loadXML($xml);
        $this->assertEquals("723346d11997b975ffea2273eb99dadd", $invoice->getFingerprint(), "Il metodo getFingerprint() non deve tener conto dei tag vuoti.");

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdPaese>IT</IdPaese>
                    <IdCodice id="test">000000</IdCodice>
                </IdTrasmittente>
                <ProgressivoInvio></ProgressivoInvio>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>';
        $invoice = InvoiceData::loadXML($xml);
        $this->assertEquals("723346d11997b975ffea2273eb99dadd", $invoice->getFingerprint(), "Il metodo getFingerprint() non deve tener conto degli attributi dei tag.");

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdPaese>IT</IdPaese>
                    <IdCodice>000001</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>';
        $invoice = InvoiceData::loadXML($xml);
        $this->assertNotEquals("723346d11997b975ffea2273eb99dadd", $invoice->getFingerprint(), "Il metodo getFingerprint() deve tener conto del valori dei tag.");

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdPaese>IT</IdPaese>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>';
        $invoice = InvoiceData::loadXML($xml);
        $this->assertNotEquals("723346d11997b975ffea2273eb99dadd", $invoice->getFingerprint(), "Il metodo getFingerprint() deve tener conto del dei tag mancanti.");

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdPaese1>IT</IdPaese1>
                    <IdCodice>000000</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>';
        $invoice = InvoiceData::loadXML($xml);
        $this->assertNotEquals("723346d11997b975ffea2273eb99dadd", $invoice->getFingerprint(), "Il metodo getFingerprint() deve tener conto del dei tag con nomi diversi.");
    }
}

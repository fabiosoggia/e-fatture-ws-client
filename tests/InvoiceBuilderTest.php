<?php

namespace CloudFinance\EFattureWsClient\Tests;

use PHPUnit\Framework\TestCase;
use CloudFinance\EFattureWsClient\InvoiceBuilder;

class InvoiceBuilderTest extends TestCase
{
    public function testNormalizeXML()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPA12" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test 01</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>';
        $builder = new InvoiceBuilder($xml);
        $this->assertXmlStringEqualsXmlString($builder->saveXML(true), '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPA12" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test 01</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>', "La normalizzazione ha apportato cambiamenti non previsti.");


        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica>
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test 01</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>';
        $builder = new InvoiceBuilder($xml);
        $this->assertXmlStringEqualsXmlString($builder->saveXML(true), '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPA12" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test 01</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>',
            "La normalizzazione non ha inserito i namespace.");


        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <FatturaElettronica>
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test 01</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </FatturaElettronica>';
        $builder = new InvoiceBuilder($xml);
        $this->assertXmlStringEqualsXmlString($builder->saveXML(true), '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPA12" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test 01</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>',
            "La normalizzazione non ha inserito i prefissi a FatturaElettronica.");


        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:t="test" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPA12" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">
            <FatturaElettronicaHeader>
                <t:DatiTrasmissione>
                <t:IdTrasmittente>
                    <t:IdCodice>Test 01</t:IdCodice>
                </t:IdTrasmittente>
                </t:DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>';
        $builder = new InvoiceBuilder($xml);
        $this->assertXmlStringEqualsXmlString($builder->saveXML(true), '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPA12" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test 01</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>',
            "La normalizzazione rimosso i prefissi non supportati.");


        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica test="test" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPA12" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">
            <FatturaElettronicaHeader test="test">
                <DatiTrasmissione test="test">
                <IdTrasmittente test="test">
                    <IdCodice>Test 01</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>';
        $builder = new InvoiceBuilder($xml);
        $this->assertXmlStringEqualsXmlString($builder->saveXML(true), '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPA12" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test 01</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>',
            "La normalizzazione rimosso gli attributi dei tag.");
    }

    public function testSet()
    {
        // $xml = file_get_contents("C:\\xampp\\htdocs\\eFATTURE-ws\\logs\\IT07945211006_1S2TQ.xml");
        // $xml = "";
        $builder = new InvoiceBuilder();
        $builder->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice", "Test 01");
        $this->assertXmlStringEqualsXmlString($builder->saveXML(true), '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPA12" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test 01</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>', "Il metodo set() non ha settato il tag /FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice.");

        $builder->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice", "Test 02");
        $this->assertXmlStringEqualsXmlString($builder->saveXML(true), '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPA12" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test 02</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>', "Il metodo set() non ha modificato il tag /FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice");

        $builder->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice", "");
        $this->assertXmlStringEqualsXmlString($builder->saveXML(true), '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPA12" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd"></p:FatturaElettronica>',
            "Il metodo set() non ha eliminato il tag /FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice.");

        $builder = new InvoiceBuilder();
        $builder->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice[1]", "Test 04.1");
        $builder->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice[2]", "Test 04.2");
        $this->assertXmlStringEqualsXmlString($builder->saveXML(true), '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPA12" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test 04.1</IdCodice>
                    <IdCodice>Test 04.2</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>',
            "Il metodo set() non ha settato il tag /FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice con indice [1] e [2].");
    }

    public function testGet()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPA12" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdCodice>Test</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>';
        $builder = new InvoiceBuilder($xml);

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
            <p:FatturaElettronica xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPA12" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">
            <FatturaElettronicaHeader>
                <DatiTrasmissione>
                <IdTrasmittente>
                    <IdPaese></IdPaese>
                    <IdCodice>Test</IdCodice>
                </IdTrasmittente>
                </DatiTrasmissione>
            </FatturaElettronicaHeader>
            </p:FatturaElettronica>';
        $builder = new InvoiceBuilder($xml);

        $result = $builder->has("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice");
        $this->assertTrue($result, "Il metodo has() non ha restituito il true.");

        $result = $builder->has("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice[1]");
        $this->assertTrue($result, "Il metodo has() non ha restituito il true [1].");

        $result = $builder->has("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice[2]");
        $this->assertFalse($result, "Il metodo has() non ha restituito il true [2].");

        $result = $builder->has("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdPaese");
        $this->assertFalse($result, "Il metodo has() ha restituito true per un nodo vuoto.");
    }
}

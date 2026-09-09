<?php

namespace CloudFinance\EFattureWsClient\Tests;

use PHPUnit\Framework\TestCase;
use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;
use CloudFinance\EFattureWsClient\V1\Enum\ErrorCodes;
use CloudFinance\EFattureWsClient\V1\LiquidazionePeriodica\LiquidazionePeriodicaTrimestrale;
use CloudFinance\EFattureWsClient\V1\LiquidazionePeriodica\XmlWrapperValidators\IVP18CommonValidator;

class LiquidazionePeriodicaTrimestraleTest extends TestCase
{
    public function testCreate()
    {
        $builder = LiquidazionePeriodicaTrimestrale::create();
        $builder->set('/Intestazione/CodiceFornitura', 'IVP18');
        $builder->set('/Intestazione/CodiceFiscaleDichiarante', 'TRNMRT75D01A783V');
        $builder->set('/Intestazione/CodiceCarica', '1');
        $builder->set('/Comunicazione/Frontespizio/CodiceFiscale', '01589730629');
        $builder->setBool('/Comunicazione/Frontespizio/FirmaDichiarazione', true);
        $builder->set('/Comunicazione/DatiContabili/Modulo/NumeroModulo', '1');
        $builder->set('/Comunicazione/DatiContabili/Modulo/Trimestre', '1');
        $builder->setFloat('/Comunicazione/DatiContabili/Modulo/TotaleOperazioniAttive', 18066.49);

        $this->assertXmlStringEqualsXmlString($builder->saveXML(true), '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Fornitura xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns="urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp">
                <Intestazione>
                    <CodiceFornitura>IVP18</CodiceFornitura>
                    <CodiceFiscaleDichiarante>TRNMRT75D01A783V</CodiceFiscaleDichiarante>
                    <CodiceCarica>1</CodiceCarica>
                </Intestazione>
                <Comunicazione>
                    <Frontespizio>
                        <CodiceFiscale>01589730629</CodiceFiscale>
                        <FirmaDichiarazione>1</FirmaDichiarazione>
                    </Frontespizio>
                    <DatiContabili>
                        <Modulo>
                            <NumeroModulo>1</NumeroModulo>
                            <Trimestre>1</Trimestre>
                            <TotaleOperazioniAttive>18066,49</TotaleOperazioniAttive>
                        </Modulo>
                    </DatiContabili>
                </Comunicazione>
            </Fornitura>',
            "XML non generato correttamente.");

        $this->assertEquals('IVP18', $builder->get('/Intestazione/CodiceFornitura'));
        $this->assertEquals('01589730629', $builder->get('/Comunicazione/Frontespizio/CodiceFiscale'));

        // $builder->validate();
    }

    public function testLoad()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <Fornitura xmlns="urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp" xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
            <Intestazione>
                <CodiceFornitura>IVP18</CodiceFornitura>
                <CodiceFiscaleDichiarante>TRNMRT75D01A783V</CodiceFiscaleDichiarante>
                <CodiceCarica>1</CodiceCarica>
            </Intestazione>
            <Comunicazione>
                <Frontespizio>
                    <CodiceFiscale>01589730629</CodiceFiscale>
                </Frontespizio>
            </Comunicazione>
        </Fornitura>';

        $builder = LiquidazionePeriodicaTrimestrale::loadXML($xml);

        $this->assertEquals('IVP18', $builder->get('/Intestazione/CodiceFornitura'));
        $this->assertEquals('01589730629', $builder->get('/Comunicazione/Frontespizio/CodiceFiscale'));


        $xml = '<?xml version="1.0" encoding="utf-8"?>
            <iv:Fornitura xmlns:sc="urn:www.agenziaentrate.gov.it:specificheTecniche:sco:common" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:cm="urn:www.agenziaentrate.gov.it:specificheTecniche:common" xmlns:iv="urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp">
                <iv:Intestazione>
                    <iv:CodiceFornitura>IVP18</iv:CodiceFornitura>
                    <iv:CodiceFiscaleDichiarante>ANNBMM72B13F839G</iv:CodiceFiscaleDichiarante>
                    <iv:CodiceCarica>1</iv:CodiceCarica>
                </iv:Intestazione>
                <iv:Comunicazione identificativo="00001">
                    <iv:Frontespizio>
                        <iv:CodiceFiscale>04143312345</iv:CodiceFiscale>
                        <iv:AnnoImposta>2022</iv:AnnoImposta>
                        <iv:PartitaIVA>04143312345</iv:PartitaIVA>
                        <iv:CFDichiarante>ANNBMM72B13F839G</iv:CFDichiarante>
                        <iv:CodiceCaricaDichiarante>1</iv:CodiceCaricaDichiarante>
                        <iv:FirmaDichiarazione>1</iv:FirmaDichiarazione>
                        <iv:CFIntermediario>CFFCFF67P19B963M</iv:CFIntermediario>
                        <iv:ImpegnoPresentazione>1</iv:ImpegnoPresentazione>
                        <iv:DataImpegno>04052022</iv:DataImpegno>
                        <iv:FirmaIntermediario>1</iv:FirmaIntermediario>
                        <iv:IdentificativoProdSoftware>10209790152</iv:IdentificativoProdSoftware>
                    </iv:Frontespizio>
                    <iv:DatiContabili>
                        <iv:Modulo>
                            <iv:NumeroModulo>1</iv:NumeroModulo>
                            <iv:Trimestre>1</iv:Trimestre>
                            <iv:TotaleOperazioniAttive>18066,49</iv:TotaleOperazioniAttive>
                            <iv:TotaleOperazioniPassive>19558,81</iv:TotaleOperazioniPassive>
                            <iv:IvaEsigibile>3974,65</iv:IvaEsigibile>
                            <iv:IvaDetratta>3699,90</iv:IvaDetratta>
                            <iv:IvaDovuta>274,75</iv:IvaDovuta>
                            <iv:InteressiDovuti>2,75</iv:InteressiDovuti>
                            <iv:ImportoDaVersare>277,50</iv:ImportoDaVersare>
                        </iv:Modulo>
                    </iv:DatiContabili>
                </iv:Comunicazione>
            </iv:Fornitura>';

        $builder = LiquidazionePeriodicaTrimestrale::loadXML($xml);

        $this->assertEquals('IVP18', $builder->get('/Intestazione/CodiceFornitura'));
        $this->assertEquals('04143312345', $builder->get('/Comunicazione/Frontespizio/CodiceFiscale'));
    }

    public function testGenerateFileName()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <Fornitura xmlns="urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp" xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
            <Intestazione>
                <CodiceFornitura>IVP18</CodiceFornitura>
                <CodiceFiscaleDichiarante>TRNMRT75D01A783V</CodiceFiscaleDichiarante>
                <CodiceCarica>1</CodiceCarica>
            </Intestazione>
            <Comunicazione>
                <Frontespizio>
                    <CodiceFiscale>01589730629</CodiceFiscale>
                </Frontespizio>
            </Comunicazione>
        </Fornitura>';

        $builder = LiquidazionePeriodicaTrimestrale::loadXML($xml);

        $this->assertEquals('IT01589730629_LI_12345', $builder->generateFileName("_12345"));

    }

    /**
     * Fornitura minima conforme allo schema: la comunicazione richiede
     * l'attributo 'identificativo' e i campi obbligatori del frontespizio.
     */
    private function validXml($identificativo = '00001')
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
        <Fornitura xmlns="urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp" xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
            <Intestazione>
                <CodiceFornitura>IVP18</CodiceFornitura>
                <CodiceFiscaleDichiarante>TRNMRT75D01A783V</CodiceFiscaleDichiarante>
                <CodiceCarica>1</CodiceCarica>
            </Intestazione>
            <Comunicazione identificativo="' . $identificativo . '">
                <Frontespizio>
                    <CodiceFiscale>04143312345</CodiceFiscale>
                    <AnnoImposta>2022</AnnoImposta>
                    <PartitaIVA>04143312345</PartitaIVA>
                    <FirmaDichiarazione>1</FirmaDichiarazione>
                </Frontespizio>
                <DatiContabili>
                    <Modulo>
                        <NumeroModulo>1</NumeroModulo>
                        <Trimestre>1</Trimestre>
                        <TotaleOperazioniAttive>18066,49</TotaleOperazioniAttive>
                    </Modulo>
                </DatiContabili>
            </Comunicazione>
        </Fornitura>';
    }

    public function testValidazioneFornituraValida()
    {
        $builder = LiquidazionePeriodicaTrimestrale::loadXML($this->validXml());
        $this->assertEquals([], $builder->getErrors(), "Una fornitura valida non deve produrre errori.");
    }

    public function testValidazioneIdentificativoMancante()
    {
        $xml = str_replace(' identificativo="00001"', '', $this->validXml());
        $builder = LiquidazionePeriodicaTrimestrale::loadXML($xml);
        $errors = $builder->getErrors();

        $this->assertArrayHasKey(ErrorCodes::IVP18_00002, $errors);
        $this->assertContains("identificativo", $errors[ErrorCodes::IVP18_00002]);
    }

    public function testValidazioneCodiceFornituraErrato()
    {
        $xml = str_replace('<CodiceFornitura>IVP18</CodiceFornitura>', '<CodiceFornitura>XXXXX</CodiceFornitura>', $this->validXml());
        $builder = LiquidazionePeriodicaTrimestrale::loadXML($xml);
        $errors = $builder->getErrors();

        $this->assertArrayHasKey(ErrorCodes::IVP18_00002, $errors);
    }

    /**
     * Il valore 2 di EventiEccezionali esiste solo nella versione aggiornata
     * dello schema: nella versione dello zip 2018 la fornitura verrebbe
     * scartata.
     */
    public function testValidazioneEventiEccezionaliValore2()
    {
        $xml = str_replace(
            '<Trimestre>1</Trimestre>',
            '<Trimestre>1</Trimestre><EventiEccezionali>2</EventiEccezionali>',
            $this->validXml());
        $builder = LiquidazionePeriodicaTrimestrale::loadXML($xml);

        $this->assertEquals([], $builder->getErrors());
    }

    public function testValidazioneDimensioneMassima()
    {
        $builder = LiquidazionePeriodicaTrimestrale::loadXML($this->validXml());

        // Un campo alfanumerico piu' grande del limite di 5 MB del canale.
        $filler = str_repeat("A", IVP18CommonValidator::MAX_FILE_SIZE);
        $builder->set('/Comunicazione/Frontespizio/IdentificativoProdSoftware', $filler);

        $errors = $builder->getErrors();
        $this->assertArrayHasKey(ErrorCodes::IVP18_00003, $errors);

        // Il controllo di dimensione precede quello di schema.
        $this->assertArrayNotHasKey(ErrorCodes::IVP18_00002, $errors);
    }

    /**
     * getErrors() puo' essere chiamato piu' volte: il validatore va registrato
     * una volta sola nel costruttore, non ad ogni chiamata.
     */
    public function testGetErrorsIdempotente()
    {
        $builder = LiquidazionePeriodicaTrimestrale::loadXML($this->validXml());

        $this->assertEquals($builder->getErrors(), $builder->getErrors());
    }

    public function testGetters()
    {
        $builder = LiquidazionePeriodicaTrimestrale::loadXML($this->validXml('00042'));

        $this->assertEquals('IVP18', $builder->getCodiceFornitura());
        $this->assertEquals('04143312345', $builder->getCodiceFiscale());
        $this->assertEquals('04143312345', $builder->getPartitaIva());
        $this->assertEquals('TRNMRT75D01A783V', $builder->getCodiceFiscaleDichiarante());
        $this->assertEquals('2022', $builder->getAnnoImposta());
        $this->assertEquals('00042', $builder->getIdentificativo());
        $this->assertEquals(1, $builder->countModuli());
    }

    public function testSetIdentificativo()
    {
        $builder = LiquidazionePeriodicaTrimestrale::loadXML($this->validXml());
        $builder->setIdentificativo('00007');

        $this->assertEquals('00007', $builder->getIdentificativo());
        $this->assertEquals([], $builder->getErrors());
    }

    public function testGenerateFileNameConTrasmittenteEsplicito()
    {
        $builder = LiquidazionePeriodicaTrimestrale::loadXML($this->validXml());

        $this->assertEquals('IT01589730629_LI_00001', $builder->generateFileName('_00001', '01589730629'));
    }

    public function testGenerateFileNameSenzaCodiceFiscale()
    {
        $builder = LiquidazionePeriodicaTrimestrale::create();

        $this->expectException(EFattureWsClientException::class);
        $builder->generateFileName('_00001');
    }
}

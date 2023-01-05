<?php

namespace CloudFinance\EFattureWsClient\Tests;

use PHPUnit\Framework\TestCase;
use CloudFinance\EFattureWsClient\V1\LiquidazionePeriodica\LiquidazionePeriodicaTrimestrale;

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
}

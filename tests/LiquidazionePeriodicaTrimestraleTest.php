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
    }
}

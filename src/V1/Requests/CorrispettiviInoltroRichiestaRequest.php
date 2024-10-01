<?php

namespace CloudFinance\EFattureWsClient\V1\Requests;

use DateTime;

class CorrispettiviInoltroRichiestaRequest implements InoltroRichiestaRequest
{
    private $elencoPiva;

    /** @var DateTime */
    private $dataRilevazioneDa;
    /** @var DateTime */
    private $dataRilevazioneA;

    private $tipoCorrispettivo = 'RT';

    /** Registratori telematici */
    const TIPO_CORRISPETTIVO_RT = 'RT';
    /** Multicassa */
    const TIPO_CORRISPETTIVO_MC = 'MC';
    /** Distributori automatici */
    const TIPO_CORRISPETTIVO_DA = 'DA';
    /** Dati contabili */
    const TIPO_CORRISPETTIVO_DC = 'DC';
    /** Registratore di cassa */
    const TIPO_CORRISPETTIVO_RC = 'RC';


    private $extra = [];

    private function __construct()
    {
    }

    /**
     * @return self
     */
    public static function make()
    {
        $instance = new self();
        return $instance;
    }

    public function setElencoPiva(string $elencoPiva)
    {
        $this->elencoPiva = strtoupper($elencoPiva);
        return $this;
    }

    public function setTipoCorrispettivo(string $tipoCorrispettivo)
    {
        $this->tipoCorrispettivo = $tipoCorrispettivo;
        return $this;
    }

    public function setExtra(array $extra)
    {
        $this->extra = $extra;
        return $this;
    }

    public function getExtra(): array
    {
        return $this->extra;
    }

    public function dataRilevazione(DateTime $da, DateTime $a)
    {
        $this->dataRilevazioneDa = $da;
        $this->dataRilevazioneA = $a;
        return $this;
    }

    public function getPiva(): string
    {
        return $this->elencoPiva;
    }

    public function getNomeFile(): string
    {
        $piva = $this->elencoPiva;
        $now = new DateTime();
        $nomeFile = "CORR_{$piva}_" . $now->format('YmdHis') . ".xml.p7m";
        return $nomeFile;
    }

    public function getXml(): string
    {
        $nomeFile = $this->getNomeFile();

        $file = '<?xml version="1.0" encoding="UTF-8"?>
            <ns1:InputMassivo
                xsi:schemaLocation="http://www.sogei.it/InputPubblico
                untitled.xsd"
                xmlns:ns1="http://www.sogei.it/InputPubblico"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <ns1:TipoRichiesta>
                    <ns1:Corrispettivi>
                        <ns1:Richiesta>CORR</ns1:Richiesta>
                        <ns1:DataRilevazione>
                            <ns1:Da>' . $this->dataRilevazioneDa->format('Y-m-d') . '</ns1:Da>
                            <ns1:A>' . $this->dataRilevazioneA->format('Y-m-d') . '</ns1:A>
                        </ns1:DataRilevazione>
                        <ns1:ElencoPiva>
                            <ns1:Piva>' . $this->getPiva() . '</ns1:Piva>
                        </ns1:ElencoPiva>
                        <ns1:TipoCorrispettivo>' . $this->tipoCorrispettivo .  '</ns1:TipoCorrispettivo>
                    </ns1:Corrispettivi>
                </ns1:TipoRichiesta>
            </ns1:InputMassivo>';
        $file = $this->prettyPrintXml($file);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <ns1:FileRichiesta xmlns:ns1="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/ServiziMassivi/input/RichiestaServiziMassivi/v1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/ServiziMassivi/input/RichiestaServiziMassivi/v1.0 RichiestaServiziMassivi_v1.0.xsd" versione="1.0">
                <TipoRichiesta>' . 'CORR' . '</TipoRichiesta>
                <NomeFile>' . $nomeFile . '</NomeFile>
                <File>' . base64_encode($file) . '</File>
            </ns1:FileRichiesta>';

        $xml = $this->prettyPrintXml($xml);

        return $xml;
    }

    private function prettyPrintXml(string $xml) : string
    {
        // Pretty print XML string
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($xml);
        $dom->formatOutput = true;
        $xml = $dom->saveXML();
        return $xml;
    }

}

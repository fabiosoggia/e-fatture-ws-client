<?php

namespace CloudFinance\EFattureWsClient\V1\Requests;

use DateTime;
use InvalidArgumentException;

/**
 * Richiesta di scarico dei documenti IVA precompilati dall'Agenzia delle
 * Entrate: registri, prospetti, bozze LIPE e dichiarazione annuale.
 *
 * Introdotta dalle specifiche "Servizi massivi di trasmissione e scarico file"
 * versione 1.5, blocco 'IvaPrecompilata' di InputMassivo_v1.5.xsd.
 *
 * @see freeinvoice-ws/src/SdI/Standard/ScaricoMassivo/InputMassivo_v1.5.xsd
 * @see freeinvoice-ws/src/SdI/Standard/ScaricoMassivo/docs/Servizi_Massivi_Specifiche_Tecniche_v1.5.pdf
 */
class DocumentiIvaInoltroRichiestaRequest implements InoltroRichiestaRequest
{
    /**
     * Valore di 'FileRichiesta/TipoRichiesta', l'involucro della richiesta.
     * Per i documenti IVA precompilati l'unico valore ammesso e' 'IVA'; i
     * codici REGI/PROS/LIPE/DICH vivono solo nel blocco interno
     * 'IvaPrecompilata' (spec. di formato v1.5, ID 1.1 e 1.1.4.1).
     */
    const RICHIESTA = 'IVA';

    /** Registri IVA vendite/acquisti */
    const TIPO_RICHIESTA_REGISTRO = 'REGI';
    /** Prospetto riepilogativo IVA */
    const TIPO_RICHIESTA_PROSPETTO = 'PROS';
    /** Bozza della comunicazione liquidazione periodica IVA */
    const TIPO_RICHIESTA_LIPE = 'LIPE';
    /** Dichiarazione annuale IVA */
    const TIPO_RICHIESTA_DICHIARAZIONE = 'DICH';

    const TIPO_OUTPUT_CSV = 'CSV';
    const TIPO_OUTPUT_XML = 'XML';
    const TIPO_OUTPUT_PDF = 'PDF';
    const TIPO_OUTPUT_TXT = 'TXT';

    const STATO_MODELLO_PRECOMPILATA = 'PRECOMPILATA';
    const STATO_MODELLO_INLAVORAZIONE = 'INLAVORAZIONE';
    const STATO_MODELLO_INVIATA = 'INVIATA';

    /** Registro completo (acquisti e vendite) */
    const TIPO_REGISTRO_COMPLETO = '0';
    /** Registro vendite */
    const TIPO_REGISTRO_VENDITE = '1';
    /** Registro acquisti */
    const TIPO_REGISTRO_ACQUISTI = '2';

    /**
     * Valori di 'Mese' che indicano un trimestre. Sono ammessi solo per il
     * prospetto riepilogativo (spec. di formato v1.5, ID 1.1.4.5).
     */
    const MESE_PRIMO_TRIMESTRE = '101';
    const MESE_SECONDO_TRIMESTRE = '102';
    const MESE_TERZO_TRIMESTRE = '103';
    const MESE_QUARTO_TRIMESTRE = '104';

    /** Primo invio */
    const TIPO_MODELLO_PRIMO_INVIO = '0';
    /** Correttiva */
    const TIPO_MODELLO_CORRETTIVA = '1';
    /** Integrativa */
    const TIPO_MODELLO_INTEGRATIVA = '2';

    private $elencoPiva;
    private $anno;
    private $mese;

    private $tipoRichiesta = self::TIPO_RICHIESTA_LIPE;
    private $tipoOutput = self::TIPO_OUTPUT_XML;
    private $statoModello = self::STATO_MODELLO_PRECOMPILATA;
    private $tipoRegistro = self::TIPO_REGISTRO_COMPLETO;
    private $tipoModello = null;

    /** @var string|null nome file, calcolato una volta sola */
    private $nomeFile = null;

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

    /**
     * Periodo di riferimento. Il 'Mese' identifica il periodo anche per le
     * richieste trimestrali, secondo le regole delle specifiche v1.5.
     *
     * @param string|int $anno formato aaaa
     * @param string|int $mese
     * @return self
     */
    public function periodo($anno, $mese)
    {
        $this->anno = (string) $anno;
        $this->mese = (string) $mese;
        return $this;
    }

    /**
     * @param string $tipoRichiesta uno dei TIPO_RICHIESTA_*
     * @return self
     */
    public function setTipoRichiesta(string $tipoRichiesta)
    {
        $ammessi = [
            self::TIPO_RICHIESTA_REGISTRO,
            self::TIPO_RICHIESTA_PROSPETTO,
            self::TIPO_RICHIESTA_LIPE,
            self::TIPO_RICHIESTA_DICHIARAZIONE,
        ];
        if (!in_array($tipoRichiesta, $ammessi, true)) {
            $ammessi = implode(", ", $ammessi);
            throw new InvalidArgumentException("Field 'tipoRichiesta' must be one of $ammessi, '$tipoRichiesta' given.");
        }
        $this->tipoRichiesta = $tipoRichiesta;
        return $this;
    }

    /**
     * @param string $tipoOutput uno dei TIPO_OUTPUT_*
     * @return self
     */
    public function setTipoOutput(string $tipoOutput)
    {
        $this->tipoOutput = $tipoOutput;
        return $this;
    }

    /**
     * @param string $statoModello uno dei STATO_MODELLO_*
     * @return self
     */
    public function setStatoModello(string $statoModello)
    {
        $this->statoModello = $statoModello;
        return $this;
    }

    /**
     * @param string $tipoRegistro uno dei TIPO_REGISTRO_*
     * @return self
     */
    public function setTipoRegistro(string $tipoRegistro)
    {
        $this->tipoRegistro = $tipoRegistro;
        return $this;
    }

    /**
     * @param string $tipoModello uno dei TIPO_MODELLO_*
     * @return self
     */
    public function setTipoModello(string $tipoModello)
    {
        $this->tipoModello = $tipoModello;
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

    public function getPiva(): string
    {
        $this->assertReady();
        return $this->elencoPiva;
    }

    public function getTipoRichiesta(): string
    {
        return $this->tipoRichiesta;
    }

    /**
     * Il nome file viene calcolato una volta sola e memorizzato: compare sia
     * nel messaggio SOAP sia dentro l'XML firmato, e i due devono coincidere.
     */
    public function getNomeFile(): string
    {
        if ($this->nomeFile === null) {
            $this->assertReady();
            $piva = $this->elencoPiva;
            $tipo = $this->tipoRichiesta;
            $now = new DateTime();
            $this->nomeFile = "{$tipo}_{$piva}_" . $now->format('YmdHis') . ".xml.p7m";
        }
        return $this->nomeFile;
    }

    /**
     * Verifica che i campi obbligatori siano valorizzati, per fallire con un
     * messaggio comprensibile invece che con un TypeError sui tipi di ritorno.
     *
     * @throws InvalidArgumentException
     * @return void
     */
    private function assertReady()
    {
        foreach ([ "elencoPiva" => $this->elencoPiva, "anno" => $this->anno, "mese" => $this->mese ] as $name => $value) {
            if ($value === null || $value === "") {
                throw new InvalidArgumentException("Field '$name' is required: call setElencoPiva() and periodo() first.");
            }
        }
    }

    /**
     * Blocco che varia in base al tipo di richiesta: è la 'xs:choice' interna
     * a 'IvaPrecompilata'.
     */
    private function getDettaglioXml(): string
    {
        if ($this->tipoRichiesta === self::TIPO_RICHIESTA_PROSPETTO) {
            return '<ns1:Prospetto>
                        <ns1:TipoRichiesta>' . self::TIPO_RICHIESTA_PROSPETTO . '</ns1:TipoRichiesta>
                    </ns1:Prospetto>';
        }

        if ($this->tipoRichiesta === self::TIPO_RICHIESTA_REGISTRO) {
            return '<ns1:Registro>
                        <ns1:TipoRichiesta>' . self::TIPO_RICHIESTA_REGISTRO . '</ns1:TipoRichiesta>
                        <ns1:TipoOutput>' . $this->tipoOutput . '</ns1:TipoOutput>
                        <ns1:TipoRegistro>' . $this->tipoRegistro . '</ns1:TipoRegistro>
                    </ns1:Registro>';
        }

        if ($this->tipoRichiesta === self::TIPO_RICHIESTA_DICHIARAZIONE) {
            $tipoModello = ($this->tipoModello === null)
                ? ''
                : '<ns1:TipoModello>' . $this->tipoModello . '</ns1:TipoModello>';
            return '<ns1:Dichiarazione>
                        <ns1:TipoRichiesta>' . self::TIPO_RICHIESTA_DICHIARAZIONE . '</ns1:TipoRichiesta>
                        <ns1:TipoOutput>' . $this->tipoOutput . '</ns1:TipoOutput>
                        <ns1:StatoModello>' . $this->statoModello . '</ns1:StatoModello>
                        ' . $tipoModello . '
                    </ns1:Dichiarazione>';
        }

        return '<ns1:Lipe>
                    <ns1:TipoRichiesta>' . self::TIPO_RICHIESTA_LIPE . '</ns1:TipoRichiesta>
                    <ns1:TipoOutput>' . $this->tipoOutput . '</ns1:TipoOutput>
                    <ns1:StatoModello>' . $this->statoModello . '</ns1:StatoModello>
                </ns1:Lipe>';
    }

    public function getXml(): string
    {
        $this->assertReady();
        $nomeFile = $this->getNomeFile();

        $file = '<?xml version="1.0" encoding="UTF-8"?>
            <ns1:InputMassivo
                xsi:schemaLocation="http://www.sogei.it/InputPubblico
                untitled.xsd"
                xmlns:ns1="http://www.sogei.it/InputPubblico"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <ns1:TipoRichiesta>
                    <ns1:IvaPrecompilata>
                        <ns1:Richiesta>' . self::RICHIESTA . '</ns1:Richiesta>
                        <ns1:ElencoPiva>
                            <ns1:Piva>' . $this->getPiva() . '</ns1:Piva>
                        </ns1:ElencoPiva>
                        <ns1:Ricerca>PUNTUALE</ns1:Ricerca>
                        <ns1:Anno>' . $this->anno . '</ns1:Anno>
                        <ns1:Mese>' . $this->mese . '</ns1:Mese>
                        ' . $this->getDettaglioXml() . '
                    </ns1:IvaPrecompilata>
                </ns1:TipoRichiesta>
            </ns1:InputMassivo>';
        $file = $this->prettyPrintXml($file);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <ns1:FileRichiesta xmlns:ns1="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/ServiziMassivi/input/RichiestaServiziMassivi/v1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/ServiziMassivi/input/RichiestaServiziMassivi/v1.0 RichiestaServiziMassivi_v1.0.xsd" versione="1.0">
                <TipoRichiesta>' . self::RICHIESTA . '</TipoRichiesta>
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

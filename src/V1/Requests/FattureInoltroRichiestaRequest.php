<?php

namespace CloudFinance\EFattureWsClient\V1\Requests;

use DateTime;

class FattureInoltroRichiestaRequest implements InoltroRichiestaRequest
{
    private $elencoPiva;
    private $nomeFile;
    private $tipoRicerca = "COMPLETA";
    private $flusso = "ALL";

    private $fattureEmesseDa;
    private $fattureEmesseA;

    private $fattureFEDisposizioneDa;
    private $fattureFEDisposizioneA;

    private $fattureRicevuteDataEmissioneDa;
    private $fattureRicevuteDataEmissioneA;

    private $fattureRicevuteDataRicezioneDa;
    private $fattureRicevuteDataRicezioneA;

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

    private function setTipoRicerca(string $tipoRicerca)
    {
        $this->tipoRicerca = $tipoRicerca;
        return $this;
    }

    function ricercaPuntuale()
    {
        return $this->setTipoRicerca('PUNTUALE');
    }

    function ricercaCompleta()
    {
        return $this->setTipoRicerca('COMPLETA');
    }

    public function setFlusso(string $flusso)
    {
        $this->flusso = $flusso;
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

    public function ricercaFattureEmesse(DateTime $da, DateTime $a)
    {
        $this->fattureEmesseDa = $da;
        $this->fattureEmesseA = $a;
        return $this;
    }

    public function ricercaFattureFEDisposizione(DateTime $da, DateTime $a)
    {
        $this->fattureFEDisposizioneDa = $da;
        $this->fattureFEDisposizioneA = $a;
        return $this;
    }

    public function ricercaFattureRicevuteDataEmissione(DateTime $da, DateTime $a)
    {
        $this->fattureRicevuteDataEmissioneDa = $da;
        $this->fattureRicevuteDataEmissioneA = $a;
        return $this;
    }

    public function ricercaFattureRicevuteDataRicezione(DateTime $da, DateTime $a)
    {
        $this->fattureRicevuteDataRicezioneDa = $da;
        $this->fattureRicevuteDataRicezioneA = $a;
        return $this;
    }

    private function toArray()
    {
        $array = [
            'NomeFile' => $this->nomeFile,
            'ElencoPiva' => [
                'Piva' => $this->elencoPiva,
            ],
            'TipoRicerca' => $this->tipoRicerca,
            'FattureEmesse' => [
                'DataEmissione' => [
                    'Da' => empty($this->fattureEmesseDa) ? null : $this->fattureEmesseDa->format('Y-m-d'),
                    'A' => empty($this->fattureEmesseA) ? null : $this->fattureEmesseA->format('Y-m-d'),
                ],
                'Flusso' => empty($this->fattureEmesseDa) ? null : $this->flusso,
            ],
            'FattureFEDisposizione' => [
                'DataEmissione' => [
                    'Da' => empty($this->fattureFEDisposizioneDa) ? null : $this->fattureFEDisposizioneDa->format('Y-m-d'),
                    'A' => empty($this->fattureFEDisposizioneA) ? null : $this->fattureFEDisposizioneA->format('Y-m-d'),
                ],
            ],
            'FattureRicevute' => [
                'DataEmissione' => [
                    'Da' => empty($this->fattureRicevuteDataEmissioneDa) ? null : $this->fattureRicevuteDataEmissioneDa->format('Y-m-d'),
                    'A' => empty($this->fattureRicevuteDataEmissioneA) ? null : $this->fattureRicevuteDataEmissioneA->format('Y-m-d'),
                ],
                'DataRicezione' => [
                    'Da' => empty($this->fattureRicevuteDataRicezioneDa) ? null : $this->fattureRicevuteDataRicezioneDa->format('Y-m-d'),
                    'A' => empty($this->fattureRicevuteDataRicezioneA) ? null : $this->fattureRicevuteDataRicezioneA->format('Y-m-d'),
                ],
                'Flusso' => empty($this->fattureRicevuteDataEmissioneDa)
                    && empty($this->fattureRicevuteDataRicezioneDa)
                    ? null : $this->flusso,
            ],
            'Flusso' => $this->flusso,
        ];

        $array = $this->removeEmptyBranchFromArray($array);

        return $array;
    }

    private function removeEmptyBranchFromArray($array)
    {
        $array = array_filter($array, function ($value) {
            if (is_array($value)) {
                $value = $this->removeEmptyBranchFromArray($value);
                return !empty($value);
            }
            return !empty($value);
        });

        return $array;
    }

    public function getPiva(): string
    {
        return $this->elencoPiva;
    }

    public function getNomeFile(): string
    {
        $piva = $this->elencoPiva;
        $now = new DateTime();
        $nomeFile = "FATT_{$piva}_" . $now->format('YmdHis') . ".xml.p7m";
        return $nomeFile;
    }

    public function getXml(): string
    {
        $requestArray = $this->toArray();
        $xmlArray = [
            'ns1:TipoRichiesta' => [
                'ns1:Fatture' => [
                    // 'ns1:Richiesta' => 'FATT',
                    // 'ns1:ElencoPiva' => [
                    //     'ns1:Piva' => $piva,
                    // ],
                    // 'ns1:TipoRicerca' => $tipoRicerca,
                    // 'ns1:FattureEmesse' => [
                    //     'ns1:DataEmissione' => [
                    //         'ns1:Da' => '2023-01-01',
                    //         'ns1:A' => '2023-04-01',
                    //     ],
                    //     'ns1:Flusso' => [
                    //         'ns1:Tutte' => 'ALL',
                    //     ],
                    //     'ns1:Ruolo' => 'CEDENTE',
                    // ],
                    // 'ns1:FattureFEDisposizione' => [
                    //     'ns1:DataEmissione' => [
                    //         'ns1:Da' => '2023-01-01',
                    //         'ns1:A' => '2023-04-01',
                    //     ],
                    //     'ns1:Ruolo' => 'CESSIONARIO',
                    // ],
                    // 'ns1:FattureRicevute' => [
                    //     'ns1:DataEmissione' => [
                    //         'ns1:Da' => '2023-01-01',
                    //         'ns1:A' => '2023-04-01',
                    //     ],
                    //     'ns1:DataRicezione' => [
                    //         'ns1:Da' => '2023-01-01',
                    //         'ns1:A' => '2023-04-01',
                    //     ],
                    //     'ns1:Flusso' => [
                    //         'ns1:Tutte' => 'ALL',
                    //     ],
                    //     'ns1:Ruolo' => 'CESSIONARIO',
                    // ],
                ],
            ],
        ];

        // $richiesta = $requestArray['Richiesta'] ?? null;
        $xmlArray['ns1:TipoRichiesta']['ns1:Fatture']['ns1:Richiesta'] = 'FATT';

        $elencoPiva = $requestArray['ElencoPiva'] ?? null;
        $piva = $elencoPiva['Piva'];
        $xmlArray['ns1:TipoRichiesta']['ns1:Fatture']['ns1:ElencoPiva'] = [
            'ns1:Piva' => $piva,
        ];

        $tipoRicerca = $requestArray['TipoRicerca'] ?? null;
        $xmlArray['ns1:TipoRichiesta']['ns1:Fatture']['ns1:TipoRicerca'] = $tipoRicerca;

        $fattureEmesse = $requestArray['FattureEmesse'] ?? null;
        if (is_array($fattureEmesse)) {
            $xmlArray['ns1:TipoRichiesta']['ns1:Fatture']['ns1:FattureEmesse'] = [
                'ns1:DataEmissione' => [
                    'ns1:Da' => $fattureEmesse['DataEmissione']['Da'],
                    'ns1:A' => $fattureEmesse['DataEmissione']['A'],
                ],
                'ns1:Flusso' => [
                    'ns1:Tutte' => $fattureEmesse['Flusso'],
                ],
                'ns1:Ruolo' => 'CEDENTE',
            ];
        }
        $fattureFEDisposizione = $requestArray['FattureFEDisposizione'] ?? null;
        if (is_array($fattureFEDisposizione)) {
            $xmlArray['ns1:TipoRichiesta']['ns1:Fatture']['ns1:FattureFEDisposizione'] = [
                'ns1:DataEmissione' => [
                    'ns1:Da' => $fattureFEDisposizione['DataEmissione']['Da'],
                    'ns1:A' => $fattureFEDisposizione['DataEmissione']['A'],
                ],
                'ns1:Ruolo' => 'CESSIONARIO',
            ];
        }
        $fattureRicevute = $requestArray['FattureRicevute'] ?? null;
        if (is_array($fattureRicevute)) {
            // i campi ns1:DataEmissione e ns1:DataRicezione sono mutualmente esclusivi
            $xmlArray['ns1:TipoRichiesta']['ns1:Fatture']['ns1:FattureRicevute'] = [];
            if (isset($fattureRicevute['DataEmissione'])) {
                $xmlArray['ns1:TipoRichiesta']['ns1:Fatture']['ns1:FattureRicevute']['ns1:DataEmissione'] = [
                    'ns1:Da' => $fattureRicevute['DataEmissione']['Da'],
                    'ns1:A' => $fattureRicevute['DataEmissione']['A'],
                ];
            }
            if (isset($fattureRicevute['DataRicezione'])) {
                $xmlArray['ns1:TipoRichiesta']['ns1:Fatture']['ns1:FattureRicevute']['ns1:DataRicezione'] = [
                    'ns1:Da' => $fattureRicevute['DataRicezione']['Da'],
                    'ns1:A' => $fattureRicevute['DataRicezione']['A'],
                ];
            }
            $xmlArray['ns1:TipoRichiesta']['ns1:Fatture']['ns1:FattureRicevute']['ns1:Flusso'] = [
                'ns1:Tutte' => $fattureRicevute['Flusso'],
            ];
            // ruolo
            $xmlArray['ns1:TipoRichiesta']['ns1:Fatture']['ns1:FattureRicevute']['ns1:Ruolo'] = 'CESSIONARIO';
        }

        $xmlString = $this->arrayToXml($xmlArray);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <ns1:InputMassivo
                xsi:schemaLocation="http://www.sogei.it/InputPubblico untitled.xsd"
                xmlns:ns1="http://www.sogei.it/InputPubblico"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                ' . $xmlString . '
            </ns1:InputMassivo>';

        $xml = $this->prettyPrintXml($xml);

        $xml = $this->buildFileRichiesta($xml);

        return $xml;
    }

    private function buildFileRichiesta(string $file)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <ns1:FileRichiesta xmlns:ns1="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/ServiziMassivi/input/RichiestaServiziMassivi/v1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/ServiziMassivi/input/RichiestaServiziMassivi/v1.0 RichiestaServiziMassivi_v1.0.xsd" versione="1.0">
                <TipoRichiesta>' . 'FATT' . '</TipoRichiesta>
                <NomeFile>' . $this->getNomeFile() . '</NomeFile>
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

    private function arrayToXml(array $xmlArray) {
        $xmlString = "";
        foreach ($xmlArray as $key => $value) {
            if (is_array($value)) {
                $xmlString .= "<$key>" . $this->arrayToXml($value) . "</$key>";
            } else {
                $xmlString .= "<$key>$value</$key>";
            }
        }
        return $xmlString;
    }

}

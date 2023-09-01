<?php

namespace CloudFinance\EFattureWsClient\V1\Requests;

use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapper;
use DateTime;

class InoltroRichiestaRequest
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
     * @return InoltroRichiestaRequest
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

    public function setNomeFile(string $nomeFile)
    {
        $this->nomeFile = $nomeFile;
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

    public function toArray()
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
        $array['Extra'] = $this->extra;

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

}

<?php

namespace CloudFinance\EFattureWsClient\V1;

use CloudFinance\EFattureWsClient\Iso3166;
use CloudFinance\EFattureWsClient\Exceptions\EFattureWsException;

class User
{
    public $nome;
    public $idPaese;
    public $codiceFiscale;
    public $partitaIva;

    public function __construct(string $nome = null) {
        $this->nome = $nome ? $nome : "";
    }

    public function isValid()
    {
        try {
            $this->validate();
        } catch (EFattureWsException $ex) {
            return false;
        }

        return true;
    }

    public function validate()
    {
        $this->nome = \trim($this->nome);

        if (\strlen($this->nome) > 256) {
            throw new EFattureWsException("Field 'nome' is longer than 256.");
        }


        $this->idPaese = \trim($this->idPaese);

        if (empty($this->idPaese)) {
            throw new EFattureWsException("Field 'idPaese' is empty.");
        }

        if (!Iso3166::isValidCountryCode($this->idPaese)) {
            throw new EFattureWsException("Field 'idPaese' is not a valid ISO3166 contry code.");
        }

        $this->codiceFiscale = \trim($this->codiceFiscale);

        if (\strlen($this->codiceFiscale) > 28) {
            throw new EFattureWsException("Field 'codiceFiscale' is longer than 28.");
        }

        $this->partitaIva = \trim($this->partitaIva);

        if (\strlen($this->partitaIva) > 28) {
            throw new EFattureWsException("Field 'partitaIva' is longer than 28.");
        }

        if (empty($this->codiceFiscal) && empty($this->partitaIva)) {
            throw new EFattureWsException("Fields 'codiceFiscale' and 'partitaIva' are both empty.");
        }
    }
}

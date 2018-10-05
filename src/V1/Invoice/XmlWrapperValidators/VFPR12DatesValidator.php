<?php

namespace CloudFinance\EFattureWsClient\V1\Invoice\XmlWrapperValidators;

use CloudFinance\EFattureWsClient\V1\Enum\ErrorCodes;
use CloudFinance\EFattureWsClient\V1\Invoice\InvoiceData;
use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapperValidator;
use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapper;
use DateTime;
use DateTimeZone;

class VFPR12DatesValidator implements XmlWrapperValidator {

    private $year;
    private $month;
    private $day;

    public function __construct(DateTime $date = null) {
        if ($date === null) {
            $date = new DateTime("now");
        }

        // Che giorno è a Roma?
        $sdiTimeZone = new DateTimeZone('Europe/Rome');
        $date->setTimeZone($sdiTimeZone);

        $this->year = $date->format("Y");
        $this->month = $date->format("m");
        $this->day = $date->format("d");
    }

    public function getErrors(XmlWrapper $xmlWrapper)
    {
        // $xmlWrapper = (InvoiceData) $xmlWrapper;

        $errors = [];

        // Codice: 00403
        // Descrizione: 2.1.1.3 <Data> successiva alla data di ricezione (la data della
        // fattura non può essere successiva alla data in cui la stessa è ricevuta dal
        // SdI)

        $FatturaElettronicaBodyCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody");
        for ($i = 1; $i <= $FatturaElettronicaBodyCount; $i++) {
            // YYYY-MM-DD
            $Data = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiGeneraliDocumento/Data");
            $DataElements = explode("-", $Data);

            if ($DataElements[0] > $this->year) {
                $errors[ErrorCodes::FPR12_00403] = "2.1.1.3 <Data> successiva alla data di ricezione ($Data > $this->year-MM-DD)";
            } else if ($DataElements[0] == $this->year) {
                if ($DataElements[1] > $this->month) {
                    $errors[ErrorCodes::FPR12_00403] = "2.1.1.3 <Data> successiva alla data di ricezione ($Data > $this->year-$this->month-DD)";
                } else if ($DataElements[1] == $this->month) {
                    if ($DataElements[2] > $this->day) {
                        $errors[ErrorCodes::FPR12_00403] = "2.1.1.3 <Data> successiva alla data di ricezione ($Data > $this->year-$this->month-$this->day)";
                    }
                }
            }
        }

        return $errors;
    }
}
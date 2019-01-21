<?php

namespace CloudFinance\EFattureWsClient\V1\Invoice\XmlWrapperValidators;

use CloudFinance\EFattureWsClient\V1\Enum\ErrorCodes;
use CloudFinance\EFattureWsClient\V1\Invoice\InvoiceData;
use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapperValidator;
use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapper;
use DateTime;

class VFPR12CommonValidator implements XmlWrapperValidator {

    public function getErrors(XmlWrapper $xmlWrapper)
    {
        // $xmlWrapper = (InvoiceData) $xmlWrapper;

        $errors = [];

        // 00001: Nome file non valido
        // 00002: Nome file duplicato
        // 00003: Le dimensioni del file superano quelle ammesse
        $fileSize = strlen($xmlWrapper->saveXML());
        if ($fileSize > 5242880) {
            // 5 megabyte
            $errors[ErrorCodes::FPR12_00003] = "Le dimensioni del file superano quelle ammesse";
            return $errors;
        }

        // 00102: Descrizione: File non integro (firma non valida)
        // 00100: Certificato di firma scaduto
        // 00101: Certificato di firma revocato
        // 00104: CA (Certification Authority) non affidabile
        // 00107: Certificato non valido
        // 00103: La firma digitale apposta manca del riferimento temporale
        // 00105: Il riferimento temporale della firma digitale apposta non è coerente
        // 00106: File / archivio vuoto o corrotto
        $schema = __DIR__. "/../../../../resources/Schema_VFPR12.xsd";
        $internalErrorPreviousValue = \libxml_use_internal_errors(true);
        $domDocument = $xmlWrapper->getDomDocument();
        $nativeErrors = [];
        \libxml_clear_errors();
        if (!$domDocument->schemaValidate($schema)) {
            $nativeErrors = \libxml_get_errors();
        }
        \libxml_use_internal_errors($internalErrorPreviousValue);
        $nativeErrorsCount = count($nativeErrors);

        $xmlWrapper->saveXML(true);
        if ($nativeErrorsCount > 50) {
            // 00201: Riscontrati più di 50 errori di formato
            $message = $nativeErrors[0]->message;
            $errors[ErrorCodes::FPR12_00201] = "Riscontrati più di 50 errori di formato ($message)";
            return $errors;
        } elseif ($nativeErrorsCount > 0) {
            // 00200: File non conforme al formato
            $message = $nativeErrors[0]->message;
            $line = $nativeErrors[0]->line;
            $column = $nativeErrors[0]->column;
            $errors[ErrorCodes::FPR12_00200] = "File non conforme al formato ($message, linea: $line, colonna: $column)";
            return $errors;
        }


        // 00300: 1.1.1.2 <IdCodice> non valido
        // 00311: 1.1.4 <CodiceDestinatario> non valido
        // 00312: 1.1.4 <CodiceDestinatario> non attivo


        // Codice: 00400
        // Descrizione in caso di fatture ordinarie: 2.2.1.14 <Natura> non presente a
        // fronte di 2.2.1.12 <AliquotaIVA> pari a zero
        // Descrizione in caso di fatture semplificate: 2.2.4 <Natura> non presente a
        // fronte di 2.2.3.2 <Aliquota> pari a zero
        // (l'indicazione di un'aliquota IVA pari a zero obbliga all'indicazione della
        // natura dell'operazione che giustifichi la non imponibilità della stessa)
        $FatturaElettronicaBodyCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody");
        for ($i = 1; $i <= $FatturaElettronicaBodyCount; $i++) {
            $DettaglioLineeCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee");
            for ($j = 1; $j <= $DettaglioLineeCount; $j++) {
                $AliquotaIVA = floatval($xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee[$j]/AliquotaIVA"));
                $Natura = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee[$j]/Natura");
                if ($AliquotaIVA === 0.00) {
                    if (empty($Natura)) {
                        $errors[ErrorCodes::FPR12_00400] = "2.2.1.14 <Natura> $Natura non presente a fronte di 2.2.1.12 <AliquotaIVA> $AliquotaIVA pari a zero";
                    }
                }
                if (!empty($Natura)) {
                    if ($AliquotaIVA !== 0.00) {
                        $errors[ErrorCodes::FPR12_00401] = "2.2.1.14 <Natura> $Natura presente a fronte di 2.2.1.12 <AliquotaIVA> $AliquotaIVA diversa da zero";
                    }
                }
            }
        }

        // Codice: 00403
        // Descrizione: 2.1.1.3 <Data> successiva alla data di ricezione (la data della
        // fattura non può essere successiva alla data in cui la stessa è ricevuta dal
        // SdI)


        // Codice: 00411
        // Descrizione: 2.1.1.5 <DatiRitenuta> non presente a fronte di almeno un
        // blocco 2.2.1 <DettaglioLinee> con 2.2.1.13 <Ritenuta> uguale a SI (la
        // presenza di una linea di fattura soggetta a ritenuta obbliga alla
        // valorizzazione del blocco <DatiRitenuta>)

        $FatturaElettronicaBodyCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody");
        for ($i = 1; $i <= $FatturaElettronicaBodyCount; $i++) {
            $DatiRitenutaCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiGeneraliDocumento/DatiRitenuta");
            $DettaglioLineeCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee");
            for ($j = 1; $j <= $DettaglioLineeCount; $j++) {
                $Ritenuta = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee[$j]/Ritenuta");
                if (($Ritenuta === "SI") && ($DatiRitenutaCount === 0)) {
                    $errors[ErrorCodes::FPR12_00411] = "2.1.1.5 <DatiRitenuta> non presente a fronte di almeno un blocco 2.2.1 <DettaglioLinee> con 2.2.1.13 <Ritenuta> uguale a SI";
                }
            }

            $Ritenuta = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiGeneraliDocumento/DatiCassaPrevidenziale/Ritenuta");
            if (($Ritenuta === "SI") && ($DatiRitenutaCount === 0)) {
                $errors[ErrorCodes::FPR12_00415] = "2.1.1.5 <DatiRitenuta> non presente a fronte di 2.1.1.7.6 <Ritenuta> uguale a SI";
            }
        }

        // Codice: 00413
        // Descrizione: 2.1.1.7.7 <Natura> non presente a fronte di 2.1.1.7.5
        // <AliquotaIVA> pari a zero (l'indicazione di un'aliquota IVA pari a zero
        // obbliga all'indicazione della natura del contributo cassa previdenziale che
        // giustifichi la non imponibilità dello stesso)
        $FatturaElettronicaBodyCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody");
        for ($i = 1; $i <= $FatturaElettronicaBodyCount; $i++) {
            $DatiCassaPrevidenzialeCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiGeneraliDocumento/DatiCassaPrevidenziale");
            for ($j = 1; $j <= $DatiCassaPrevidenzialeCount; $j++) {
                $AliquotaIVA = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiGeneraliDocumento/DatiCassaPrevidenziale[$j]/AliquotaIVA");
                $Natura = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiGeneraliDocumento/DatiCassaPrevidenziale[$j]/Natura");

                if ($AliquotaIVA === "0.00") {
                    if (empty($Natura)) {
                        $errors[ErrorCodes::FPR12_00413] = "2.1.1.7.7 <Natura> non presente a fronte di 2.1.1.7.5 <AliquotaIVA> pari a zero";
                    }
                }
                if (!empty($Natura)) {
                    if ($AliquotaIVA !== "0.00") {
                        $errors[ErrorCodes::FPR12_00414] = "2.1.1.7.7 <Natura> presente a fronte di 2.1.1.7.5 <Aliquota IVA> diversa da zero";
                    }
                }
            }
        }

        // Codice: 00417
        // Descrizione in caso di fatture ordinarie: 1.4.1.1 <IdFiscaleIVA> e 1.4.1.2
        // <CodiceFiscale> non valorizzati
        // Descrizione in caso di fatture semplificate: 1.3.1.1 <IdFiscaleIVA> e 1.3.1.2
        // <CodiceFiscale> non valorizzati
        // (per il cessionario/committente deve essere indicato almeno uno tra partita
        // IVA e codice fiscale)
        $IdFiscaleIVACount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/IdFiscaleIVA");
        $CodiceFiscaleCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/CodiceFiscale");
        if (($IdFiscaleIVACount === 0) && ($CodiceFiscaleCount === 0)) {
            $errors[ErrorCodes::FPR12_00417] = "1.4.1.1 <IdFiscaleIVA> e 1.4.1.2 <CodiceFiscale> non valorizzati";
        }

        // Codice: 00418
        // Descrizione in caso di fatture ordinarie: 2.1.1.3 <Data> antecedente a
        // 2.1.6.3 <Data>
        // Descrizione in caso di fatture semplificate: 2.1.1.3 <Data> antecedente a
        // 2.1.2.2 <DataFR>
        // (la data della fattura non può essere antecedente a quella del documento
        // al quale la stessa si collega)
        $FatturaElettronicaBodyCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody");
        for ($i = 1; $i <= $FatturaElettronicaBodyCount; $i++) {
            $Data1 = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiGeneraliDocumento/Data");
            $Data1DT = DateTime::createFromFormat("Y-m-d", $Data1);
            $DatiFattureCollegateCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiFattureCollegate");
            for ($j = 1; $j <= $DatiFattureCollegateCount; $j++) {
                $Data2 = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiFattureCollegate[$j]/Data");
                $Data2DT = DateTime::createFromFormat("Y-m-d", $Data2);
                if ($Data1DT < $Data2DT) {
                    $errors[ErrorCodes::FPR12_00418] = "2.1.1.3 <Data> antecedente a 2.1.6.3 <Data>";
                }
            }
        }

        // Codice: 00419
        // Descrizione: 2.2.2 <DatiRiepilogo> non presente in corrispondenza di
        // almeno un valore di 2.1.1.7.5 <AliquotaIVA> o 2.2.1.12 <AliquotaIVA> (per
        // ogni aliquota IVA presente in fattura deve esistere il corrispondente blocco
        // di <DatiRiepilogo>)
        $AliquoteIVAInDatiRiepilogo = [];
        $FatturaElettronicaBodyCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody");
        for ($i = 1; $i <= $FatturaElettronicaBodyCount; $i++) {
            $DatiRiepilogoCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DatiRiepilogo");
            for ($j = 1; $j <= $DatiRiepilogoCount; $j++) {
                $AliquotaIVA = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DatiRiepilogo[$j]/AliquotaIVA");
                if (floatval($AliquotaIVA) == 0) {
                    continue;
                }
                $AliquoteIVAInDatiRiepilogo[] = $AliquotaIVA;
            }


            $DatiCassaPrevidenzialeCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiGeneraliDocumento/DatiCassaPrevidenziale");
            for ($j = 1; $j <= $DatiCassaPrevidenzialeCount; $j++) {
                $AliquotaIVA = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiGeneraliDocumento/DatiCassaPrevidenziale[$j]/AliquotaIVA");
                if (floatval($AliquotaIVA) == 0) {
                    continue;
                }
                if (in_array($AliquotaIVA, $AliquoteIVAInDatiRiepilogo)) {
                    continue;
                }
                $errors[ErrorCodes::FPR12_00419] = "2.2.2 <DatiRiepilogo> non presente in corrispondenza di almeno un valore di 2.1.1.7.5 <AliquotaIVA> (per ogni aliquota IVA presente in fattura deve esistere il corrispondente blocco di <DatiRiepilogo>)";
                break;
            }

            $DettaglioLineeCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee");
            for ($j = 1; $j <= $DettaglioLineeCount; $j++) {
                $AliquotaIVA = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee[$j]/AliquotaIVA");
                if (floatval($AliquotaIVA) == 0) {
                    continue;
                }
                if (in_array($AliquotaIVA, $AliquoteIVAInDatiRiepilogo)) {
                    continue;
                }
                $errors[ErrorCodes::FPR12_00419] = "2.2.2 <DatiRiepilogo> non presente in corrispondenza di almeno un valore di 2.2.1.12 <AliquotaIVA> (per ogni aliquota IVA presente in fattura deve esistere il corrispondente blocco di <DatiRiepilogo>)";
                break;
            }
        }


        // Codice: 00420
        // Descrizione: 2.2.2.2 <Natura> con valore N6 (inversione contabile) a fronte
        // di 2.2.2.7 <EsigibilitaIVA> uguale a S (scissione pagamenti) (il regime di
        // scissione pagamenti non è compatibile con quello di inversione contabile –
        // reverse charge)
        // (vale solo per le fatture ordinarie)

        $FatturaElettronicaBodyCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody");
        for ($i = 1; $i <= $FatturaElettronicaBodyCount; $i++) {
            $DatiRiepilogoCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DatiRiepilogo");
            for ($j = 1; $j <= $DatiRiepilogoCount; $j++) {
                $Natura = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DatiRiepilogo[$j]/Natura");
                $EsigibilitaIVA = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DatiRiepilogo[$j]/EsigibilitaIVA");
                if (($Natura === "N6") && ($EsigibilitaIVA === "S")) {
                    $errors[ErrorCodes::FPR12_00420] = "2.2.2.2 <Natura> con valore N6 (inversione contabile) a fronte di 2.2.2.7 <EsigibilitaIVA> uguale a S (scissione pagamenti) (il regime di scissione pagamenti non è compatibile con quello di inversione contabile – reverse charge)";
                }
            }
        }

        // Codice: 00421
        // Descrizione: 2.2.2.6 <Imposta> non calcolato secondo le regole definite
        // nelle specifiche tecniche (il valore dell'elemento <Imposta> deve essere
        // uguale al risultato della seguente operazione:
        //
        //         ( AliquotaIVA * ImponibileImporto ) ⁄ 100 )
        //
        // il risultato di questa operazione va arrotondato alla seconda cifra decimale,
        // per difetto se la terza cifra decimale è inferiore a 5, per eccesso se uguale
        // o superiore a 5; è ammessa la tolleranza di ±1 centesimo di euro)

        $FatturaElettronicaBodyCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody");
        for ($i = 1; $i <= $FatturaElettronicaBodyCount; $i++) {
            $DatiRiepilogoCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DatiRiepilogo");
            for ($j = 1; $j <= $DatiRiepilogoCount; $j++) {
                $AliquotaIVA = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DatiRiepilogo[$j]/AliquotaIVA");
                $AliquotaIVA = floatval($AliquotaIVA);
                $ImponibileImporto = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DatiRiepilogo[$j]/ImponibileImporto");
                $ImponibileImporto = floatval($ImponibileImporto);
                $Imposta = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DatiRiepilogo[$j]/Imposta");
                $Imposta = floatval($Imposta);

                $ImpostaExpected = round(($AliquotaIVA * $ImponibileImporto) / 100, 2);
                $diff = round(abs($ImpostaExpected - $Imposta), 2);

                if ($diff > 0.01) {
                    $errors[ErrorCodes::FPR12_00421] = "2.2.2.6 <Imposta> [$Imposta] non calcolato secondo le regole definite nelle specifiche tecniche (il valore dell'elemento <Imposta> deve essere uguale al risultato della seguente operazione: ( AliquotaIVA * ImponibileImporto ) ⁄ 100 ) [ ( $AliquotaIVA * $ImponibileImporto ) ⁄ 100 ) ] il risultato di questa operazione va arrotondato alla seconda cifra decimale, per difetto se la terza cifra decimale è inferiore a 5, per eccesso se uguale o superiore a 5; è ammessa la tolleranza di ±1 centesimo di euro)";
                }
            }
        }

        // Codice: 00422
        // Descrizione: 2.2.2.5 <ImponibileImporto> non calcolato secondo le regole
        // definite nelle specifiche tecniche (il valore dell'elemento
        // <ImponibileImporto> deve essere uguale, per ogni valore distinto di
        // aliquota IVA, al risultato della seguente operazione:
        //
        //      Σ(n) PrezzoTotale[y] + Σ(m) ImportoContributoCassa[x] + Σ(t) Arrotondamento[z]
        //
        // dove n è il numero di linee di dettaglio con stessa aliquota IVA, m è il
        // numero di blocchi di dati cassa previdenziale con stessa aliquota IVA, t è il
        // numero di blocchi di dati riepilogo con stessa aliquota IVA; è ammessa la
        // tolleranza di ±1 euro)

        $FatturaElettronicaBodyCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody");
        for ($i = 1; $i <= $FatturaElettronicaBodyCount; $i++) {
            $DatiRiepilogoCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DatiRiepilogo");
            $DettaglioLineeCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee");
            $DatiCassaPrevidenzialeCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiGeneraliDocumento/DatiCassaPrevidenziale");
            for ($j = 1; $j <= $DatiRiepilogoCount; $j++) {
                $AliquotaIVA = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DatiRiepilogo[$j]/AliquotaIVA");
                // Da verificare
                $Arrotondamento = 0.00;
                $PrezzoTotale = 0.00;
                $ImportoContributoCassa = 0.00;
                $ImponibileImporto = 0.00;

                for ($k = 1; $k <= $DatiRiepilogoCount; $k++) {
                    $AliquotaIVAY = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DatiRiepilogo[$k]/AliquotaIVA");

                    if ($AliquotaIVAY !== $AliquotaIVA) {
                        continue;
                    }

                    $ImponibileImportoY = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DatiRiepilogo[$k]/ImponibileImporto");
                    $ImponibileImportoY = floatval($ImponibileImportoY);
                    $ImponibileImporto += $ImponibileImportoY;

                    $ArrotondamentoY = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DatiRiepilogo[$k]/Arrotondamento");
                    $ArrotondamentoY = floatval($ArrotondamentoY);
                    $Arrotondamento += $ArrotondamentoY;
                }

                for ($k = 1; $k <= $DettaglioLineeCount; $k++) {
                    $AliquotaIVAY = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee[$k]/AliquotaIVA");

                    if ($AliquotaIVAY !== $AliquotaIVA) {
                        continue;
                    }

                    $PrezzoTotaleY = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee[$k]/PrezzoTotale");
                    $PrezzoTotaleY = floatval($PrezzoTotaleY);
                    $PrezzoTotale += $PrezzoTotaleY;
                }


                for ($k = 1; $k <= $DatiCassaPrevidenzialeCount; $k++) {
                    $AliquotaIVAY = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiGeneraliDocumento/DatiCassaPrevidenziale[$k]/AliquotaIVA");


                    if ($AliquotaIVAY !== $AliquotaIVA) {
                        continue;
                    }

                    $ImportoContributoCassaY = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiGeneraliDocumento/DatiCassaPrevidenziale[$k]/ImportoContributoCassa");
                    $ImportoContributoCassaY = floatval($ImportoContributoCassaY);
                    $ImportoContributoCassa += $ImportoContributoCassaY;
                }


                $ImponibileImportoExpected = $PrezzoTotale + $ImportoContributoCassa + $Arrotondamento;
                $ImponibileImportoExpected = \round($ImponibileImportoExpected, 2);
                $diff = round(abs($ImponibileImportoExpected - $ImponibileImporto), 2);

                if ($diff > 1) {
                    $errors[ErrorCodes::FPR12_00422] = "2.2.2.5 <ImponibileImporto> non calcolato secondo le regole definite nelle specifiche tecniche";
                }
            }
        }


        // Codice: 00423
        // Descrizione: 2.2.1.11 <PrezzoTotale> non calcolato secondo le regole
        // definite nelle specifiche tecniche (il valore dell'elemento <PrezzoTotale>
        // deve essere uguale al risultato della seguente operazione:
        //      (PrezzoUnitario ± ScontoMaggiorazione) * Quantita
        // è ammessa la tolleranza di ±1 centesimo di euro)

        $FatturaElettronicaBodyCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody");
        for ($i = 1; $i <= $FatturaElettronicaBodyCount; $i++) {
            $DettaglioLineeCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee");
            for ($j = 1; $j <= $DettaglioLineeCount; $j++) {
                $PrezzoTotale = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee[$j]/PrezzoTotale");
                $PrezzoTotale = floatval($PrezzoTotale);
                $PrezzoUnitario = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee[$j]/PrezzoUnitario");
                $PrezzoUnitario = floatval($PrezzoUnitario);
                $PrezzoUnitarioComputed = $PrezzoUnitario;
                $ScontoMaggiorazioneCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee[$j]/ScontoMaggiorazione");
                for ($k = 1; $k <= $ScontoMaggiorazioneCount; $k++) {
                    $Tipo = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee[$j]/ScontoMaggiorazione[$k]/Tipo");
                    $Importo = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee[$j]/ScontoMaggiorazione[$k]/Importo");
                    if ($Importo !== null) {
                        $Importo = floatval($Importo);
                        if ($Tipo === "SC") {
                            $PrezzoUnitarioComputed -= $Importo;
                        } else {
                            $PrezzoUnitarioComputed += $Importo;
                        }
                    } else {
                        $Percentuale = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee[$j]/ScontoMaggiorazione[$k]/Percentuale");
                        $Percentuale = floatval($Percentuale) / 100;
                        if ($Tipo === "SC") {
                            $PrezzoUnitarioComputed -= $PrezzoUnitarioComputed * $Percentuale;
                        } else {
                            $PrezzoUnitarioComputed += $PrezzoUnitarioComputed * $Percentuale;
                        }
                    }
                }
                $Quantita = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee[$j]/Quantita", 1);
                $Quantita = floatval($Quantita);
                $PrezzoTotaleExpected = $PrezzoUnitarioComputed * $Quantita;
                if (abs($PrezzoTotaleExpected - $PrezzoTotale) > 0.01) {
                    $errors[ErrorCodes::FPR12_00423] = "2.2.1.11 <PrezzoTotale> non calcolato secondo le regole definite nelle specifiche tecniche (atteso: $PrezzoTotaleExpected dichiarato: $PrezzoTotale).";
                }
            }
        }


        // Codice: 00424
        // Descrizione in caso di fatture ordinarie: 2.2.1.12 <AliquotaIVA> o 2.2.2.1
        // <AliquotaIVA> o 2.1.1.7.5 <AliquotaIVA> non indicata in termini percentuali
        // Descrizione in caso di fatture semplificate: 2.2.3.2 <Aliquota> non indicata
        // in termini percentuali
        // (l'aliquota IVA va sempre espressa in termini percentuali; ad esempio
        // un'aliquota del 10% va indicata con 10.00 e non con 0.10)


        // Codice: 00425
        // Descrizione: 2.1.1.4 <Numero> non contenente caratteri numerici (il
        // numero della fattura deve contenere almeno un carattere numerico)
        // (vale sia per le fatture ordinarie che per le semplificate)
        $FatturaElettronicaBodyCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody");
        for ($i = 1; $i <= $FatturaElettronicaBodyCount; $i++) {
            $Numero = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiGeneraliDocumento/Numero");
            $res = preg_match('/\d/', $Numero);
            if ($res !== 1) {
                $errors[ErrorCodes::FPR12_00425] = "2.1.1.4 <Numero> non contenente caratteri numerici (il numero della fattura deve contenere almeno un carattere numerico)";
            }
        }


        // Codice: 00427
        // Descrizione: 1.1.4 <CodiceDestinatario> di 7 caratteri a fronte di 1.1.3
        // <FormatoTrasmissione> con valore FPA12 o 1.1.4 <CodiceDestinatario> di
        // 6 caratteri a fronte di 1.1.3 <FormatoTrasmissione> con valore FPR12
        // (vale solo per le fatture ordinarie)
        $FormatoTrasmissione = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/FormatoTrasmissione");
        $FormatoTrasmissione = \strtoupper($FormatoTrasmissione);
        $CodiceDestinatario = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/CodiceDestinatario");
        if (($FormatoTrasmissione === "FPA12") && (\strlen($CodiceDestinatario) === 7)) {
            $errors[ErrorCodes::FPR12_00427] = "1.1.4 <CodiceDestinatario> di 7 caratteri a fronte di 1.1.3 <FormatoTrasmissione> con valore FPA12";
        }
        if (($FormatoTrasmissione === "FPR12") && (\strlen($CodiceDestinatario) === 6)) {
            $errors[ErrorCodes::FPR12_00427] = "1.1.4 <CodiceDestinatario> di 6 caratteri a fronte di 1.1.3 <FormatoTrasmissione> con valore FPR12";
        }


        // Codice: 00428
        // Descrizione: 1.1.3 <FormatoTrasmissione> non coerente con il valore
        // dell'attributo VERSION
        // (vale sia per le fatture ordinarie che per le semplificate)
        $FormatoTrasmissione = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/FormatoTrasmissione");
        if (($FormatoTrasmissione !== "FPA12") && ($FormatoTrasmissione !== "FPR12")) {
            $errors[ErrorCodes::FPR12_00428] = "1.1.3 <FormatoTrasmissione> con valore diverso da FPA12 e FPR12";
        }
        $versione = $xmlWrapper->getAttribute("/FatturaElettronica", "versione");
        if ($versione !== $FormatoTrasmissione) {
            $errors[ErrorCodes::FPR12_00428] = "1.1.3 <FormatoTrasmissione> non coerente con il valore dell'attributo VERSION";
        }

        // Codice: 00429
        // Descrizione: 2.2.2.2 <Natura> non presente a fronte di 2.2.2.1
        // <AliquotaIVA> pari a zero (nei <DatiRiepilogo>, l'indicazione di un'aliquota
        // IVA pari a zero obbliga all'indicazione della natura che giustifichi la non
        // imponibilità)
        $FatturaElettronicaBodyCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody");
        for ($i = 1; $i <= $FatturaElettronicaBodyCount; $i++) {
            $DatiRiepilogoCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DatiRiepilogo");
            for ($j = 1; $j <= $DatiRiepilogoCount; $j++) {
                $AliquotaIVA = floatval($xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DatiRiepilogo[$j]/AliquotaIVA"));
                $Natura = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DatiRiepilogo[$j]/Natura");
                if (($AliquotaIVA === 0.00) && empty($Natura)) {
                    $errors[ErrorCodes::FPR12_00429] = "2.2.2.2 <Natura> $Natura non presente a fronte di 2.2.2.1 <AliquotaIVA> $AliquotaIVA pari a zero (nei <DatiRiepilogo>, l'indicazione di un'aliquota IVA pari a zero obbliga all'indicazione della natura che giustifichi la non imponibilità)";
                }
                if (($AliquotaIVA !== 0.00) && !empty($Natura)) {
                    $errors[ErrorCodes::FPR12_00430] = "2.2.2.2 <Natura> $Natura presente a fronte di 2.2.2.1 <AliquotaIVA> $AliquotaIVA diversa da zero (l'indicazione di un'aliquota IVA diversa da zero qualifica i dati di riepilogo come dati riferiti ad operazioni imponibili e quindi non è ammessa la presenza dell'elemento <Natura>)";
                }
            }
        }

        // Codice: 00437
        // Descrizione: 2.1.1.8.2 <Percentuale> e 2.1.1.8.3 <Importo> non presenti a
        // fronte di 2.1.1.8.1 <Tipo> valorizzato (l'indicazione della presenza di uno
        // sconto o di una maggiorazione, obbliga all'indicazione di almeno uno degli
        // elementi <Percentuale> e <Importo> dello sconto/maggiorazione)
        // (vale solo per le fatture ordinarie)
        $FatturaElettronicaBodyCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody");
        for ($i = 1; $i <= $FatturaElettronicaBodyCount; $i++) {
            $ScontoMaggiorazioneCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiGeneraliDocumento/ScontoMaggiorazione");
            for ($j = 1; $j <= $ScontoMaggiorazioneCount; $j++) {
                $Tipo = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiGeneraliDocumento/ScontoMaggiorazione[$j]/Tipo");
                $Percentuale = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiGeneraliDocumento/ScontoMaggiorazione[$j]/Percentuale");
                $Importo = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiGenerali/DatiGeneraliDocumento/ScontoMaggiorazione[$j]/Importo");
                if (!empty($Tipo)) {
                    if (empty($Percentuale) && empty($Importo)) {
                        $errors[ErrorCodes::FPR12_00437] = "2.1.1.8.2 <Percentuale> e 2.1.1.8.3 <Importo> non presenti a fronte di 2.1.1.8.1 <Tipo> valorizzato (l'indicazione della presenza di uno sconto o di una maggiorazione, obbliga all'indicazione di almeno uno degli elementi <Percentuale> e <Importo> dello sconto/maggiorazione)";
                    }
                }
            }
        }

        // Codice: 00438
        // Descrizione: 2.2.1.10.2 <Percentuale> e 2.2.1.10.3 <Importo> non presenti
        // a fronte di 2.2.1.10.1 <Tipo> valorizzato (l'indicazione della presenza di
        // uno sconto o di una maggiorazione, obbliga all'indicazione di almeno uno
        // degli elementi <Percentuale> e <Importo> dello sconto/maggiorazione)
        // (vale solo per le fatture ordinarie)
        $FatturaElettronicaBodyCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody");
        for ($i = 1; $i <= $FatturaElettronicaBodyCount; $i++) {
            $DettaglioLineeCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee");
            for ($j = 1; $j <= $DettaglioLineeCount; $j++) {
                $ScontoMaggiorazioneCount = $xmlWrapper->count("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee[$j]/ScontoMaggiorazione");
                for ($k = 1; $k <= $ScontoMaggiorazioneCount; $k++) {
                    $Tipo = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee[$j]/ScontoMaggiorazione[$k]/Tipo");
                    $Percentuale = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee[$j]/ScontoMaggiorazione[$k]/Percentuale");
                    $Importo = $xmlWrapper->get("/FatturaElettronica/FatturaElettronicaBody[$i]/DatiBeniServizi/DettaglioLinee[$j]/ScontoMaggiorazione[$k]/Importo");
                    if (!empty($Tipo)) {
                        if (empty($Percentuale) && empty($Importo)) {
                            $errors[ErrorCodes::FPR12_00438] = "2.2.1.10.2 <Percentuale> e 2.2.1.10.3 <Importo> non presenti a fronte di 2.2.1.10.1 <Tipo> valorizzato (l'indicazione della presenza di uno sconto o di una maggiorazione, obbliga all'indicazione di almeno uno degli elementi <Percentuale> e <Importo> dello sconto/maggiorazione)";
                        }
                    }
                }
            }
        }


        return $errors;
    }
}
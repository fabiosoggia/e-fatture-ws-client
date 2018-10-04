<?php

namespace CloudFinance\EFattureWsClient\V1\Invoice;

class ErrorsEnum {

    // ---------------------------------------------------------------------- //
    //                          Errori di sistema                             //
    // ---------------------------------------------------------------------- //

    const SYS_00001 = "SYS_00001";
    const SYS_00001_MSG = "Errore inatteso";

    const SYS_00002 = "SYS_00002";
    const SYS_00002_MSG = "Missing request parameter";

    const SYS_00003 = "SYS_00003";
    const SYS_00003_MSG = "Invalid request parameter";

    const SYS_00004 = "SYS_00004";
    const SYS_00004_MSG = "Authentication error";

    // ---------------------------------------------------------------------- //
    //                     Errori di trasmissione fattura                     //
    // ---------------------------------------------------------------------- //

    const FPR12_0000A = "FPR12_0000A";
    const FPR12_0000A_MSG = "Permessi non validi per l'operazione";

    const FPR12_0000B = "FPR12_0000B";
    const FPR12_0000B_MSG = "Fattura modificata erroneamente";


    // ---------------------------------------------------------------------- //
    //            Errori di trasmissione fattura & Errori dell'SdI            //
    // ---------------------------------------------------------------------- //

    // Non gestito
    const FPR12_00001 = "00001";
    const FPR12_00001_MSG = "Nome file non valido";

    // Non gestito
    const FPR12_00002 = "00002";
    const FPR12_00002_MSG = "Nome file duplicato";

    // Gestito su server
    const FPR12_00003 = "00003";
    const FPR12_00003_MSG = "Le dimensioni del file superano quelle ammesse";

    // Gestito su server
    const FPR12_00102 = "00102";
    const FPR12_00102_MSG = "File non integro (firma non valida)";

    // Gestito su server
    const FPR12_00100 = "00100";
    const FPR12_00100_MSG = "Certificato di firma scaduto";

    // Gestito su server
    const FPR12_00101 = "00101";
    const FPR12_00101_MSG = "Certificato di firma revocato";

    // Gestito su server
    const FPR12_00104 = "00104";
    const FPR12_00104_MSG = "CA (Certification Authority) non affidabile";

    // Gestito su server
    const FPR12_00103 = "00103";
    const FPR12_00103_MSG = "La firma digitale apposta manca del riferimento temporale";

    // Gestito su server
    const FPR12_00105 = "00105";
    const FPR12_00105_MSG = "Il riferimento temporale della firma digitale apposta non è coerente";

    // Non gestito
    const FPR12_00106 = "00106";
    const FPR12_00106_MSG = "File / archivio vuoto o corrotto";

    // Gestito su server
    const FPR12_00107 = "00107";
    const FPR12_00107_MSG = "Certificato non valido";

    // Gestito
    const FPR12_00200 = "00200";
    const FPR12_00200_MSG = "File non conforme al formato";

    // Gestito
    const FPR12_00201 = "00201";
    const FPR12_00201_MSG = "Riscontrati più di 50 errori di formato";

    // Non gestito
    const FPR12_00300 = "00300";
    const FPR12_00300_MSG = "1.1.1.2 <IdCodice> non valido";

    // Non gestito
    const FPR12_00301 = "00301";
    const FPR12_00301_MSG = "1.2.1.1.2 <IdCodice> non valido";

    // Non gestito
    const FPR12_00302 = "00302";
    const FPR12_00302_MSG = "Il Codice Fiscale del Cedente/Prestatore non è valido";

    // Non gestito
    const FPR12_00303 = "00303";
    const FPR12_00303_MSG = "1.3.1.1.2 <IdCodice> o 1.4.4.1.2 <IdCodice> non valido";

    // Non gestito
    const FPR12_00304 = "00304";
    const FPR12_00304_MSG = "Codice Fiscale del Cedente/Prestatore non presente in Anagrafe Tributaria";

    // Non gestito
    const FPR12_00305 = "00305";
    const FPR12_00305_MSG = "1.4.1.1.2 <IdCodice> non valido";

    // Non gestito
    const FPR12_00306 = "00306";
    const FPR12_00306_MSG = "1.4.1.2 <CodiceFiscale> non valido";

    // Non gestito
    const FPR12_00311 = "00311";
    const FPR12_00311_MSG = "1.1.4 <CodiceDestinatario> non valido";

    // Non gestito
    const FPR12_00312 = "00312";
    const FPR12_00312_MSG = "1.1.4 <CodiceDestinatario> non attivo";

    // Non gestito
    const FPR12_00398 = "00398";
    const FPR12_00398_MSG = "Codice Ufficio presente ed univocamente identificabile nell’anagrafica IPA di riferimento, in presenza di 1.1.4 <CodiceDestinatario> valorizzato con codice ufficio 'Centrale'";

    // Non gestito
    const FPR12_00399 = "00399";
    const FPR12_00399_MSG = "CodiceFiscale del CessionarioCommittente presente nell’anagrafica IPA di riferimento in presenza di 1.1.4 <CodiceDestinatario> valorizzato  a 999999";

    // Gestito
    const FPR12_00400 = "00400";
    const FPR12_00400_MSG = "2.2.1.14 <Natura> non presente a fronte di 2.2.1.12 <AliquotaIVA> pari a zero";

    // Gestito
    const FPR12_00401 = "00401";
    const FPR12_00401_MSG = "2.2.1.14 <Natura> presente a fronte di 2.2.1.12 <AliquotaIVA> diversa da zero";

    // Gestito su server
    const FPR12_00403 = "00403";
    const FPR12_00403_MSG = "2.1.1.3 <Data> successiva alla data di ricezione (la data della fattura non può essere successiva alla data in cui la stessa è ricevuta dal SdI)";

    // Gestito su server
    const FPR12_00404 = "00404";
    const FPR12_00404_MSG = "Fattura duplicata";

    // Gestito su server
    const FPR12_00409 = "00409";
    const FPR12_00409_MSG = "Fattura duplicata nel lotto";

    // Gestito
    const FPR12_00411 = "00411";
    const FPR12_00411_MSG = "2.1.1.5 <DatiRitenuta> non presente a fronte di almeno un blocco 2.2.1 <DettaglioLinee> con 2.2.1.13 <Ritenuta> uguale a SI";

    // Gestito
    const FPR12_00413 = "00413";
    const FPR12_00413_MSG = "2.1.1.7.7 <Natura> non presente a fronte di 2.1.1.7.5 <AliquotaIVA> pari a zero";

    // Gestito
    const FPR12_00414 = "00414";
    const FPR12_00414_MSG = "2.1.1.7.7 <Natura> presente a fronte di 2.1.1.7.5 <Aliquota IVA> diversa da zero";

    // Gestito
    const FPR12_00415 = "00415";
    const FPR12_00415_MSG = "2.1.1.5 <DatiRitenuta> non presente a fronte di 2.1.1.7.6 <Ritenuta> uguale a SI";

    // Gestito
    const FPR12_00417 = "00417";
    const FPR12_00417_MSG = "1.4.1.1 <IdFiscaleIVA> e 1.4.1.2 <CodiceFiscale> non valorizzati (almeno uno dei due deve essere valorizzato)";

    // Gestito
    const FPR12_00418 = "00418";
    const FPR12_00418_MSG = "2.1.1.3 <Data> antecedente a 2.1.6.3 <Data>";

    // Gestito
    const FPR12_00419 = "00419";
    const FPR12_00419_MSG = "2.2.2 <DatiRiepilogo> non presente in corrispondenza di almeno un valore di 2.1.1.7.5 <AliquotaIVA> o 2.2.1.12 <AliquotaIVA> (per ogni aliquota IVA presente in fattura deve esistere il corrispondente blocco di <DatiRiepilogo>)";

    // Gestito
    const FPR12_00420 = "00420";
    const FPR12_00420_MSG = "2.2.2.2 <Natura> con valore N6 (inversione contabile) a fronte di 2.2.2.7 <EsigibilitaIVA> uguale a S (scissione pagamenti) (il regime di scissione pagamenti non è compatibile con quello di inversione contabile – reverse charge)";

    // Gestito
    const FPR12_00421 = "00421";
    const FPR12_00421_MSG = "2.2.2.6 <Imposta> non calcolato secondo le regole definite nelle specifiche tecniche (il valore dell'elemento <Imposta> deve essere uguale al risultato della seguente operazione: ( AliquotaIVA * ImponibileImporto ) ⁄ 100 ) il risultato di questa operazione va arrotondato alla seconda cifra decimale, per difetto se la terza cifra decimale è inferiore a 5, per eccesso se uguale o superiore a 5; è ammessa la tolleranza di ±1 centesimo di euro)";

    // Gestito
    const FPR12_00422 = "00422";
    const FPR12_00422_MSG = "2.2.2.5 <ImponibileImporto> non calcolato secondo le regole definite nelle specifiche tecniche";

    // Gestito
    const FPR12_00423 = "00423";
    const FPR12_00423_MSG = "2.2.1.11 <PrezzoTotale> non calcolato secondo le regole definite nelle specifiche tecniche";

    // Non gestito
    const FPR12_00424 = "00424";
    const FPR12_00424_MSG = "2.2.1.12 <AliquotaIVA> o 2.2.2.1 <AliquotaIVA> o 2.1.1.7.5 <AliquotaIVA> non indicata in termini percentuali";

    // Gestito
    const FPR12_00425 = "00425";
    const FPR12_00425_MSG = "2.1.1.4 <Numero> non contenente caratteri numerici (il numero della fattura deve contenere almeno un carattere numerico)";

    // Gestito
    const FPR12_00427 = "00427";
    const FPR12_00427_MSG = "1.1.4 <CodiceDestinatario> di 7 caratteri a fronte di 1.1.3 <FormatoTrasmissione> con valore FPA12 o 1.1.4 <CodiceDestinatario> di 6 caratteri a fronte di 1.1.3 <FormatoTrasmissione> con valore FPR12";

    // Gestito
    const FPR12_00428 = "00428";
    const FPR12_00428_MSG = "1.1.3 <FormatoTrasmissione> con valore diverso da FPA12 e FPR12";

    // Gestito
    const FPR12_00429 = "00429";
    const FPR12_00429_MSG = "2.2.2.2 <Natura> non presente a fronte di 2.2.2.1 <AliquotaIVA> pari a zero (nei <DatiRiepilogo>, l'indicazione di un'aliquota IVA pari a zero obbliga all'indicazione della natura che giustifichi la non imponibilità)";

    // Gestito
    const FPR12_00430 = "00430";
    const FPR12_00430_MSG = "2.2.2.2 <Natura> presente a fronte di 2.2.2.1 <AliquotaIVA> diversa da zero (l'indicazione di un'aliquota IVA diversa da zero qualifica i dati di riepilogo come dati riferiti ad operazioni imponibili e quindi non è ammessa la presenza dell'elemento <Natura>)";

    // Gestito
    const FPR12_00437 = "00437";
    const FPR12_00437_MSG = "2.1.1.8.2 <Percentuale> e 2.1.1.8.3 <Importo> non presenti a fronte di 2.1.1.8.1 <Tipo> valorizzato (l'indicazione della presenza di uno sconto o di una maggiorazione, obbliga all'indicazione di almeno uno degli elementi <Percentuale> e <Importo> dello sconto/maggiorazione)";

    // Gestito
    const FPR12_00438 = "00438";
    const FPR12_00438_MSG = "2.2.1.10.2 <Percentuale> e 2.2.1.10.3 <Importo> non presenti a fronte di 2.2.1.10.1 <Tipo> valorizzato (l'indicazione della presenza di uno sconto o di una maggiorazione, obbliga all'indicazione di almeno uno degli elementi <Percentuale> e <Importo> dello sconto/maggiorazione)";


    // ---------------------------------------------------------------------- //
    //                    Errori di trasmissione notifica                     //
    // ---------------------------------------------------------------------- //

    const ES01 = "ES01";
    const ES01_MSG = "File validato";

    const ES02 = "ES02";
    const ES02_MSG = "File validato con segnalazione";

    const ES03 = "ES03";
    const ES03_MSG = "File scartato";
}
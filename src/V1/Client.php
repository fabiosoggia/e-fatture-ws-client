<?php

namespace CloudFinance\EFattureWsClient\V1;

use CloudFinance\EFattureWsClient\V1\Invoice\InvoiceData;
use CloudFinance\EFattureWsClient\V1\Invoice\ErrorsEnum;
use CloudFinance\EFattureWsClient\V1\Digest;
use CloudFinance\EFattureWsClient\Exceptions\ApiRequestException;
use CloudFinance\EFattureWsClient\Exceptions\ApiResponseException;
use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use CloudFinance\EFattureWsClient\V1\Invoice\SignedInvoiceReader;
use League\ISO3166\ISO3166;
use CloudFinance\EFattureWsClient\V1\Invoice\NotificaEsito;

class Client
{
    private $uuid;
    private $privateKey;

    public $method = "POST";
    public $endpoint = "http://localhost/eFATTURE-ws/public/api/v1/";
    public $timeout = 5.0;
    public $verify = true;

    /**
     * Questi messaggi le risposte ricevute dal ws dal sdi quando sono inviate
     * le fatture/notifiche.
     */
    public const WEBHOOK_KIND_API_INVIO_FATTURA = "webhook_kind_api_invio_fattura";
    public const WEBHOOK_KIND_API_INVIO_NOTIFICA = "webhook_kind_api_invio_notifica";


    /**
     * È il file inviato dal SdI al soggetto ricevente insieme al file fattura e
     * contenente i dati principali di riferimento del file utili per
     * l’elaborazione, ivi compreso l’identificativo del SdI.
     */
    public const WEBHOOK_KIND_SDI_RICEVI_FATTURA = "riceviFatture";


    /**
     * È la notifica inviata dal SdI al soggetto trasmittente nei casi in cui
     * non sia stato superato uno o più controlli tra quelli effettuati dal SdI
     * sul file ricevuto.
     */
    public const WEBHOOK_KIND_SDI_NOTIFICA_SCARTO = "notificaScarto";


    /**
     * È la notifica inviata dal SdI al soggetto trasmittente per attestare
     * l’avvenuta ricezione della fattura e l’impossibilità di recapitare il
     * file al destinatario; la casistica si riferisce:
     *  - alla presenza del codice destinatario valorizzato a “999999” e
     *    all’impossibilità di identificare univocamente nell’anagrafica di
     *    riferimento, IPA, un ufficio di fatturazione elettronica associato al
     *    codice fiscale corrispondente all’identificativo fiscale del
     *    cessionario\committente riportato in fattura;
     *  - alla mancata disponibilità tecnica di comunicazione con il destinatario.
     */
    // public const WEBHOOK_KIND_SDI_TRASMISSIONE_SENZA_RECAPITO_FATTURA = "notificaMancataConsegna";


    /**
     * È la ricevuta inviata dal SdI al soggetto trasmittente per comunicare
     * l’avvenuta consegna del file al destinatario.
     */
    public const WEBHOOK_KIND_SDI_NOTIFICA_RICEVUTA_CONSEGNA = "ricevutaConsegna";
    /**
     * È la notifica inviata dal SdI al soggetto trasmittente nei casi in cui
     * fallisca l’operazione di consegna del file al destinatario.
     */
    public const WEBHOOK_KIND_SDI_NOTIFICA_MANCATA_CONSEGNA = "notificaMancataConsegna";


    /**
     * È la notifica inviata dal SdI al soggetto trasmittente per comunicare
     * l’esito (accettazione o rifiuto della fattura) dei controlli effettuati
     * sul documento ricevuto dal destinatario.
     */
    public const WEBHOOK_KIND_SDI_NOTIFICA_ESITO = "notificaEsito";
    /**
     * È la notifica inviata dal SdI al soggetto ricevente per comunicare
     * eventuali incoerenze o errori nell’esito inviato al SdI precedentemente
     * (accettazione o rifiuto della fattura).
     */
    public const WEBHOOK_KIND_SDI_NOTIFICA_SCARTO_ESITO = "notificaScarto";


    /**
     * È la notifica inviata dal SdI sia al soggetto trasmittente che al
     * soggetto ricevente per comunicare la decorrenza del termine limite per
     * la comunicazione dell’accettazione/rifiuto.
     */
    public const WEBHOOK_KIND_SDI_NOTIFICA_DECORRENZA_TERMINI = "notificaDecorrenzaTermini";


    public function setUuid(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public function setPrivateKey(string $privateKey)
    {
        $this->privateKey = $privateKey;
    }

    public function parse($efSignature, $efPayload)
    {
        if (empty($efSignature)) {
            throw new \InvalidArgumentException("Parameter 'efSignature' can't be empty.");
        }
        if (empty($efPayload)) {
            throw new \InvalidArgumentException("Parameter 'efPayload' can't be empty.");
        }

        $digest = new Digest($this->uuid, $this->privateKey, $efPayload);
        if (!$digest->verify($efSignature)) {
            throw new \InvalidArgumentException("Mismatching signature.");
        }

        $data = [];
        $data["webhookKind"] = __::get($efPayload, "webhookKind");
        $data["sdiInvoiceFileId"] = intval(__::get($efPayload, "sdiInvoiceFileId"));

        if ($data["sdiInvoiceFileId"] <= 0) {
            throw new EFattureWsClientException("Invalid payload received.");
        }

        $sdiNotificationFileId = intval(__::get($efPayload, "sdiInvoiceFileId"));
        if ($sdiNotificationFileId > 0) {
            $data["sdiNotificationFileId"] = $sdiNotificationFileId;
        }

        return (object) $data;
    }

    public function executeHttpRequest($command, array $payload)
    {
        $method = \strtoupper($this->method);
        $command = \strtolower($command);
        $apiUuid = $this->uuid;
        $fingerprint = $this->createDigest($payload);
        $options = [
            'form_params' => [
                'apiUuid' => $apiUuid,
                'fingerprint' => $fingerprint,
                'payload' => $payload
            ]
        ];
        $client = new \GuzzleHttp\Client([
            'base_uri' => $this->endpoint,
            'timeout'  => $this->timeout,
            'verify' => $this->verify
        ]);

        $request = null;
        $response = null;

        try {
            $response = $client->request($method, $command, $options);
        } catch (TransferException $ex) {
            if (!$ex->hasResponse()) {
                throw $ex;
            }

            $request = $ex->getRequest();
            $response = $ex->getResponse();
        }

        $responseBody = (string) $response->getBody();
        $responseJson = json_decode($responseBody, true);
        if ($responseJson === null) {
            throw new RequestException(
                "Unable to parse response",
                $request,
                $response,
                $ex
            );
        }

        if (!isset($responseJson["success"])) {
            throw new RequestException(
                "Missing 'success' attribute",
                $request,
                $response
            );
        }

        $success = $responseJson["success"];

        if ($success) {
            if (!isset($responseJson["data"])) {
                throw new RequestException(
                    "Missing 'data' attribute",
                    $request,
                    $response
                );
            }

            $responseData = $responseJson["data"];
            return $responseData;
        }

        if (!isset($responseJson["errorCode"])) {
            throw new RequestException(
                "Missing 'errorCode' attribute",
                $request,
                $response
            );
        }
        $errorCode = $responseJson["errorCode"];

        if (!isset($responseJson["errorMessage"])) {
            throw new RequestException(
                "Missing 'errorMessage' attribute",
                $request,
                $response
            );
        }
        $errorMessage = $responseJson["errorMessage"];

        throw new ApiResponseException(
            $errorCode,
            $errorMessage,
            $request,
            $response
        );
    }

    public function createDigest(array $payload)
    {
        $digest = new Digest($this->uuid, $this->privateKey, $payload);
		return (string) $digest;
    }

    /**
     * Invia una fattura non firmata.
     *
     * @throws CloudFinance\EFattureWsClient\Exceptions\ApiExceptionInterface
     * @param InvoiceData $invoice
     * @return array
     */
    public function sendInvoice(InvoiceData $invoice)
    {
        // Compila campi "obbligatori"
        $invoice->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdPaese", "IT");
        $invoice->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice", \str_repeat("0", 28));
        $invoice->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/ProgressivoInvio", \str_repeat("0", 10));

        // Valida contenuto della fattura
        $invoice->validate();

        // Effettua la richiesta
        $invoiceXml = $invoice->saveXML();
        $payload = [ "invoiceXml" => $invoiceXml ];
        $response = $this->executeHttpRequest("invoices", $payload);
        return $response;
    }

    /**
     * Carica una fattura firmata.
     *
     * @throws CloudFinance\EFattureWsClient\Exceptions\ApiExceptionInterface
     * @param SignedInvoiceReader $signedInvoiceReader
     * @return array
     */
    public function uploadInvoice(SignedInvoiceReader $signedInvoiceReader)
    {
        $invoice = $signedInvoiceReader->getInvoiceData();

        // Valida contenuto della fattura
        $invoice->validate();

        $signingMethod = $signedInvoiceReader->getSigningMethod();
        $signedInvoiceXml = $signedInvoiceReader->getFileSignedContent();

        if (\strlen($signedInvoiceXml) > 4718592) {
            throw new ApiRequestException("The invoice size is bigger than 5MB.", ErrorsEnum::FPR12_00003_MSG);
        }

        $payload = [
            "signingMethod" => $signingMethod,
            "signedInvoiceXml" => $signedInvoiceXml
        ];
        $response = $this->executeHttpRequest("files", $payload);
        return $response;
    }

    /**
     * Setta i permessi di invio/ricezione fatture per un codice fiscale o
     * partita iva.
     *
     * @throws CloudFinance\EFattureWsClient\Exceptions\ApiExceptionInterface
     * @param string $kind può assumere il valore 'cf' o 'piva'
     * @param string $idPaese codice del paese del codice
     * @param string $codice codice fiscale o partita iva in base a quanto specificato in $kind
     * @param boolean $receives se true (false) abilita (disabilita) la ricezione delle fatture
     * @param boolean $transmits se true (false) abilita (disabilita) l'invio delle fatture
     * @return array
     */
    public function setUser(string $kind, string $idPaese, string $codice, bool $receives, bool $transmits)
    {
        $kind = \strtolower(\trim($kind));
        if (!in_array($kind, ["cf", "piva"])) {
            throw new ApiRequestException("Field 'kind' must be 'cf' or 'piva'.", ErrorsEnum::SYS_00003);
        }

        $idPaese = \strtoupper(\trim($idPaese));
        try {
            (new ISO3166)->alpha2($idPaese);
        } catch (\Exception $ex) {
            throw new ApiRequestException("Field 'kind' is not a valid ISO3166 country code.", ErrorsEnum::SYS_00003);
        }

        $codice = \strtolower(\trim($codice));
        if (empty($codice)) {
            throw new ApiRequestException("Field 'codice' is empty.", ErrorsEnum::SYS_00003);
        }
        if (strlen($codice) > 28) {
            throw new ApiRequestException("Field 'codice' is longer than 28 characters.", ErrorsEnum::SYS_00003);
        }

        $payload = [
            "kind" => $kind,
            "idPaese" => $idPaese,
            "codice" => $codice,
            "receives" => \intval($receives) . "",
            "transmits" => \intval($transmits) . ""
        ];

        $response = $this->executeHttpRequest("users", $payload);
        return $response;
    }

    /**
     * Invia una notifica di esito per la fattura RICEVUTA.
     *
     * @param integer $sdiInvoiceFileId
     * @param boolean $accept
     * @param string $descrizione
     * @param string $riferimentoFattura Descrive a quale fattura si riferisce l’esito; se non valorizzato si intende riferito a tutte le fatture presenti nel file.
     * @return void
     */
    public function sendEsito(int $sdiInvoiceFileId, NotificaEsito $notificaEsito)
    {
        $notificaEsito->setIdentificativoSdi("111");
        $notificaEsito->validate();

        $payload = [
            "sdiInvoiceFileId" => $sdiInvoiceFileId . "",
            "notificaEsitoXml" => $notificaEsito->saveXML()
        ];

        $response = $this->executeHttpRequest("notifications", $payload);
        return $response;
    }
}

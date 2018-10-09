<?php

namespace CloudFinance\EFattureWsClient\V1;

use CloudFinance\EFattureWsClient\Exceptions\ApiRequestException;
use CloudFinance\EFattureWsClient\Exceptions\ApiResponseException;
use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;
use CloudFinance\EFattureWsClient\V1\Digest;
use CloudFinance\EFattureWsClient\V1\Enum\ErrorCodes;
use CloudFinance\EFattureWsClient\V1\Invoice\InvoiceData;
use CloudFinance\EFattureWsClient\V1\Invoice\NotificaEsito;
use CloudFinance\EFattureWsClient\V1\Invoice\SignedInvoiceReader;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use League\ISO3166\ISO3166;
use CloudFinance\EFattureWsClient\V1\Enum\WebhookMessages;

class Client
{
    private $uuid;
    private $privateKey;

    public $method = "POST";
    public $endpoint = "http://localhost/eFATTURE-ws/public/api/v1/";
    public $timeout = 5.0;
    public $verify = true;

    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;
    }

    public function parse($fiRequest)
    {
        if (empty($fiRequest)) {
            throw new \InvalidArgumentException("Parameter 'fiRequest' can't be empty.");
        }

        $efPayload = $fiRequest["payload"];
        $efSignature = $fiRequest["fingerprint"];
        $digest = new Digest($this->uuid, $this->privateKey, $efPayload);
        if (!$digest->verify($efSignature)) {
            throw new \InvalidArgumentException("Mismatching signature.");
        }

        $data = [];
        $data['webhookMessage'] = $efPayload["webhookMessage"];
        $data['sdiNotification'] = $efPayload["sdiNotification"];
        $data['sdiInvoiceFileId'] = intval($efPayload["sdiInvoiceFileId"]);
        $data['sdiNotificationFileId'] = intval($efPayload["sdiNotificationFileId"]);

        if ($data["sdiInvoiceFileId"] <= 0) {
            throw new EFattureWsClientException("Invalid payload received.");
        }

        $data['content'] = $efPayload["content"];
        $data['content'] = WebhookMessages::unserializeMessage($data["content"]);

        return (object) $data;
    }

    public function buildRequestArray(array $data)
    {
        $apiUuid = $this->uuid;
        $payload = $data;
        $fingerprint = $this->createDigest($payload);

        return [
            "_fiRequest" => [
                'apiUuid' => $apiUuid,
                'fingerprint' => $fingerprint,
                'payload' => $payload
            ]
        ];
    }

    public function executeHttpRequest($command, array $data)
    {
        $method = \strtoupper($this->method);
        $command = \strtolower($command);
        $apiUuid = $this->uuid;
        $options = [
            'form_params' => $this->buildRequestArray($data),
            'allow_redirects' => [
                'strict' => true
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
                $response
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
            throw new ApiRequestException("The invoice size is bigger than 5MB.", ErrorCodes::FPR12_00003_MSG);
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
            throw new ApiRequestException("Field 'kind' must be 'cf' or 'piva'.", ErrorCodes::SYS_00003);
        }

        $idPaese = \strtoupper(\trim($idPaese));
        try {
            (new ISO3166)->alpha2($idPaese);
        } catch (\Exception $ex) {
            throw new ApiRequestException("Field 'kind' is not a valid ISO3166 country code.", ErrorCodes::SYS_00003);
        }

        $codice = \strtolower(\trim($codice));
        if (empty($codice)) {
            throw new ApiRequestException("Field 'codice' is empty.", ErrorCodes::SYS_00003);
        }
        if (strlen($codice) > 28) {
            throw new ApiRequestException("Field 'codice' is longer than 28 characters.", ErrorCodes::SYS_00003);
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

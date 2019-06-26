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

    public $endpoint = "https://stg-ws-sdi.cloudfinancegroup.com:8443/api/v1/";
    public $timeout = 120.0;
    public $verify = true;

    public function setUuid($uuid)
    {
        if (!is_string($uuid)) {
            $givenType = (\is_object($uuid)) ? get_class($uuid) : gettype($uuid);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        $this->uuid = $uuid;
    }

    public function setPrivateKey($privateKey)
    {
        if (!is_string($privateKey)) {
            $givenType = (\is_object($privateKey)) ? get_class($privateKey) : gettype($privateKey);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        $this->privateKey = $privateKey;
    }

    public function verify($fiRequest)
    {
        if (!is_array($fiRequest)) {
            $givenType = (\is_object($fiRequest)) ? get_class($fiRequest) : gettype($fiRequest);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "array", $givenType);
            throw new \InvalidArgumentException($message);
        }

        if (empty($fiRequest)) {
            throw new \InvalidArgumentException("Parameter 'fiRequest' can't be empty.");
        }

        $efPayload = $fiRequest["payload"];
        $efSignature = $fiRequest["fingerprint"];
        $digest = new Digest($this->uuid, $this->privateKey, $efPayload);
        if (!$digest->verify($efSignature)) {
            throw new EFattureWsClientException("Mismatching signature.");
        }
    }

    public function parse($fiRequest)
    {
        if (!is_array($fiRequest)) {
            $givenType = (\is_object($fiRequest)) ? get_class($fiRequest) : gettype($fiRequest);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "array", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $efPayload = json_decode($fiRequest["payload"], true);
        $efSignature = $fiRequest["fingerprint"];

        $data = [];
        $data['id'] = intval($efPayload["id"]);
        $data['webhookId'] = intval($efPayload["webhookId"]);
        $data['webhookAttempt'] = intval($efPayload["webhookAttempt"]);
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

    public function buildRequestArray($data)
    {
        if (!is_string($data)) {
            $givenType = (\is_object($data)) ? get_class($data) : gettype($data);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

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

    public function executeHttpRequest($command, $data, $method = "POST")
    {
        if (!is_string($command)) {
            $givenType = (\is_object($command)) ? get_class($command) : gettype($command);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        if (!is_array($data)) {
            $givenType = (\is_object($data)) ? get_class($data) : gettype($data);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 2, __METHOD__, "array", $givenType);
            throw new \InvalidArgumentException($message);
        }

        if (!is_string($data)) {
            $data = json_encode($data);
        }

        $method = \strtoupper($method);
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
                "Unable to parse response:\n\n$responseBody",
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

    public function createDigest($payload)
    {
        if (!is_string($payload)) {
            $givenType = (\is_object($payload)) ? get_class($payload) : gettype($payload);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

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
    public function sendInvoice(InvoiceData $invoice, $username = "", $password = "")
    {
        $format = $invoice->getFormatoTrasmissione();
        if ($format === InvoiceData::FATTURA_FSM) {
            throw new ApiRequestException("The invoice format can't be FSM10.", ErrorCodes::FPR12_00200);
        }

        // Compila campi "obbligatori"
        $invoice->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdPaese", "IT");
        $invoice->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice", \str_repeat("0", 28));
        if (!$invoice->hasValue("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/ProgressivoInvio")) {
            $invoice->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/ProgressivoInvio", \str_repeat("0", 10));
        }

        // Valida contenuto della fattura
        $invoice->normalize();
        $invoice->validate();

        // Effettua la richiesta
        $invoiceXml = $invoice->saveXML();
        $payload = [
            "invoiceXml" => $invoiceXml,
            "username" => $username,
            "password" => $password,
        ];
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

        $format = $invoice->getFormatoTrasmissione();
        if ($format === InvoiceData::FATTURA_FSM) {
            throw new ApiRequestException("The invoice format can't be FSM10.", ErrorCodes::FPR12_00200);
        }

        // Valida contenuto della fattura
        $invoice->validate();

        $signingMethod = $signedInvoiceReader->getSigningMethod();
        $signedInvoiceXml = $signedInvoiceReader->getFileSignedContent();

        if (\strlen($signedInvoiceXml) > 4718592) {
            throw new ApiRequestException("The invoice size is bigger than 5MB.", ErrorCodes::FPR12_00003_MSG);
        }

        $payload = [
            "signingMethod" => $signingMethod,
            "signedInvoiceXml" => base64_encode($signedInvoiceXml)
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
    public function setUser($kind, $idPaese, $codice, $receives, $transmits)
    {
        if (!is_string($kind)) {
            $givenType = (\is_object($kind)) ? get_class($kind) : gettype($kind);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        if (!is_string($idPaese)) {
            $givenType = (\is_object($idPaese)) ? get_class($idPaese) : gettype($idPaese);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 2, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        if (!is_string($codice)) {
            $givenType = (\is_object($codice)) ? get_class($codice) : gettype($codice);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 3, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        if (!is_bool($receives)) {
            $givenType = (\is_object($receives)) ? get_class($receives) : gettype($receives);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 4, __METHOD__, "bool", $givenType);
            throw new \InvalidArgumentException($message);
        }
        if (!is_bool($transmits)) {
            $givenType = (\is_object($transmits)) ? get_class($transmits) : gettype($transmits);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 5, __METHOD__, "bool", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $kind = \strtolower(\trim($kind));
        if (!in_array($kind, ["cf", "piva"])) {
            throw new ApiRequestException("Field 'kind' must be 'cf' or 'piva', value '$kind' given.", ErrorCodes::SYS_00003);
        }

        $idPaese = \strtoupper(\trim($idPaese));
        try {
            (new ISO3166)->alpha2($idPaese);
        } catch (\Exception $ex) {
            throw new ApiRequestException("Value '$idPaese' is not a valid ISO3166 country code.", ErrorCodes::SYS_00003);
        }

        $codice = \strtolower(\trim($codice));
        if (empty($codice)) {
            throw new ApiRequestException("Field 'codice' is empty.", ErrorCodes::SYS_00003);
        }
        if (strlen($codice) > 28) {
            throw new ApiRequestException("Field 'codice' is longer than 28 characters, value '$codice' given.", ErrorCodes::SYS_00003);
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
    public function sendEsito($sdiInvoiceFileId, NotificaEsito $notificaEsito)
    {
        if (!is_int($sdiInvoiceFileId)) {
            $givenType = (\is_object($sdiInvoiceFileId)) ? get_class($sdiInvoiceFileId) : gettype($sdiInvoiceFileId);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "int", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $notificaEsito->setIdentificativoSdi("111");
        $notificaEsito->normalize();
        $notificaEsito->validate();

        $payload = [
            "sdiInvoiceFileId" => $sdiInvoiceFileId . "",
            "notificaEsitoXml" => $notificaEsito->saveXML()
        ];

        $response = $this->executeHttpRequest("notifications", $payload);
        return $response;
    }

    public function getFile($fileUuid)
    {
        if (!is_string($fileUuid)) {
            $givenType = (\is_object($fileUuid)) ? get_class($fileUuid) : gettype($fileUuid);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $payload = [
            "fileUuid" => $fileUuid . ""
        ];

        $response = $this->executeHttpRequest("files", $payload, "GET");
        return $response;
    }
}

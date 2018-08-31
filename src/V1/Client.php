<?php

namespace CloudFinance\EFattureWsClient\V1;

use CloudFinance\EFattureWsClient\V1\Invoice\InvoiceData;
use CloudFinance\EFattureWsClient\V1\Digest;
use CloudFinance\EFattureWsClient\Exceptions\RequestException;
use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;
use GuzzleHttp\Exception\TransferException;
use CloudFinance\EFattureWsClient\V1\Invoice\SignedInvoiceReader;
use League\ISO3166\ISO3166;

class Client
{
    private $uuid;
    private $privateKey;

    public $method = "POST";
    public $endpoint = "http://localhost/eFATTURE-ws/public/api/v1/";
    public $timeout = 2.0;

    public function setUuid(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public function setPrivateKey(string $privateKey)
    {
        $this->privateKey = $privateKey;
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
        ]);

        try {
            $request = $client->request($method, $command, $options);
        } catch (TransferException $ex) {
            if (!$ex->hasResponse()) {
                throw $ex;
            }

            $request = $ex->getRequest();
            $response = $ex->getResponse();
            $responseBody = (string) $response->getBody();
            $responseJson = json_decode($responseBody, true);
            $responseMessage = isset($responseJson["error"]) ?
                $responseJson["error"] : $ex->getMessage();

            if (empty($responseMessage)) {
                throw $ex;
            }

            throw new RequestException(
                $responseMessage,
                $request,
                $response,
                $ex
            );
        }

        return $request;
    }

    public function createDigest(array $payload)
    {
        $digest = new Digest($this->uuid, $this->privateKey, $payload);
		return (string) $digest;
    }

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
        $responseBody = (string) $response->getBody();

        $responseJson = json_decode($responseBody, true);

        if (empty($responseJson)) {
            throw new EFattureWsClientException("Server responded with unparsable message: \n\n $responseBody");
        }

        return $responseJson;
    }

    public function uploadInvoice(SignedInvoiceReader $signedInvoiceReader)
    {
        $invoice = $signedInvoiceReader->getInvoiceData();

        // Valida contenuto della fattura
        $invoice->validate();

        $signingMethod = $signedInvoiceReader->getSigningMethod();
        $signedInvoiceXml = $signedInvoiceReader->getFileSignedContent();
        $payload = [
            "signingMethod" => $signingMethod,
            "signedInvoiceXml" => $signedInvoiceXml
        ];
        $response = $this->executeHttpRequest("files", $payload);
        $responseBody = (string) $response->getBody();

        if (empty($responseJson)) {
            throw new EFattureWsClientException("Server responded with unparsable message: \n\n $responseBody");
        }

        return $responseJson;
    }

    public function setUser(string $kind, string $idPaese, string $codice, bool $receives, bool $transmits)
    {
        $kind = \strtolower(\trim($kind));
        if (!in_array($kind, ["cf", "piva"])) {
            throw new EFattureWsClientException("Field 'kind' must be 'cf' or 'piva'.");
        }

        $idPaese = \strtoupper(\trim($idPaese));
        try {
            (new ISO3166)->alpha2($idPaese);
        } catch (\Exception $ex) {
            throw new EFattureWsClientException("Field 'kind' is not a valid ISO3166 country code.");
        }

        $codice = \strtolower(\trim($codice));
        if (empty($codice)) {
            throw new EFattureWsClientException("Field 'codice' is empty.");
        }
        if (strlen($codice) > 28) {
            throw new EFattureWsClientException("Field 'codice' is longer than 28 characters.");
        }

        $payload = [
            "kind" => $kind,
            "idPaese" => $idPaese,
            "codice" => $codice,
            "receives" => \intval($receives) . "",
            "transmits" => \intval($transmits) . ""
        ];

        $response = $this->executeHttpRequest("users", $payload);
        $responseBody = (string) $response->getBody();
        $responseJson = json_decode($responseBody, true);

        return $responseJson;
    }
}

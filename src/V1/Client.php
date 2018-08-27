<?php

namespace CloudFinance\EFattureWsClient\V1;

use CloudFinance\EFattureWsClient\InvoiceBuilder;
use CloudFinance\EFattureWsClient\Exceptions\RequestException;
use GuzzleHttp\Exception\TransferException;

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
        $payload = ksort($payload);
		$apiKey = $this->privateKey;
		$messageDigest = \hash_hmac("sha256", json_encode($payload), $apiKey);
		return $messageDigest;
    }

    public function sendInvoice(InvoiceBuilder $invoice)
    {
        // Compila campi "obbligatori"
        $invoice->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdPaese", "IT");
        $invoice->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice", \str_repeat("0", 28));
        $invoice->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/ProgressivoInvio", \str_repeat("0", 10));

        // Valida contenuto della fattura
        $invoice->validate();

        // Effettua la richiesta
        $xml = $invoice->saveXML();
        $payload = [ "invoice" => $xml ];
        $response = $this->executeHttpRequest("invoice", $payload);
        $responseBody = (string) $response->getBody();
        $responseJson = json_decode($responseBody, true);

        if (empty($responseJson)) {
            throw new RequestException("Server responded with empty response.");
        }

        return $responseJson;
    }
}

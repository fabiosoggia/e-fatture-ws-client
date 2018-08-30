<?php

namespace CloudFinance\EFattureWsClient\V1\Invoice;

use CloudFinance\EFattureWsClient\V1\Invoice\InvoiceData;
use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;

define("SIGNING_METHOD_CADES_BES", "CAdES-BES");
define("SIGNING_METHOD_XADES_BES", "XAdES-BES");

class SignedInvoiceReader
{
    private $filePlainContent;
    private $filePlainFingerprint;

    private $fileSignedContent;
    private $fileSignedFingerprint;

    private $signingMethod;
    private $invoiceData;

    private function __construct() {
    }

    public static function getSigningMethodByFileName(string $fileName)
    {
        $fileName = \trim($fileName);

        if (preg_match('/.xml.p7m$/i', $fileName) === 1) {
            return SIGNING_METHOD_CADES_BES;
        }
        if (preg_match('/.xml$/i', $fileName) === 1) {
            return SIGNING_METHOD_XADES_BES;
        }
        return false;
    }

    public static function getFileExtensionBySigningMethod(string $signingMethod)
    {
        if ($signingMethod === SIGNING_METHOD_CADES_BES) {
            return "xml.p7m";
        }
        if ($signingMethod === SIGNING_METHOD_XADES_BES) {
            return "xml";
        }
        return false;
    }

    public static function createFromSignedString($signingMethod, $string)
    {
        if (!in_array($signingMethod, [ SIGNING_METHOD_CADES_BES, SIGNING_METHOD_XADES_BES ])) {
            throw new EFattureWsClientException("Field 'signingMethod' must be 'CAdES-BES' or 'XAdES-BES'.");
        }

        $invoice = new self;

        $fileSignedPath = \tempnam(\sys_get_temp_dir(), "fsp");
        $filePlainPath = \tempnam(\sys_get_temp_dir(), "fpp");

        if (($fileSignedPath === false) || ($filePlainPath === false)) {
            throw new EFattureWsClientException("Unable to create temporary files.");
        }

        $res = \file_put_contents($fileSignedPath, $string);
        if ($res === false) {
            throw new EFattureWsClientException("Unable to write temporary files.");
        }

        if ($signingMethod === "CAdES-BES") {
            $openSslCommand = "openssl smime -verify -noverify -in '%s' -inform DER -out '%s'";
            $openSslCommand = \sprintf($openSslCommand, $fileSignedPath, $filePlainPath);
            $res = \exec($openSslCommand, $output);
        }

        if ($signingMethod === "XAdES-BES") {
            $res = \file_put_contents($filePlainPath, $string);
        }

        $invoice->fileSignedContent = $string;
        $invoice->fileSignedFingerprint = \md5($invoice->fileSignedContent);

        $invoice->filePlainContent = \file_get_contents($filePlainPath);
        $invoice->filePlainFingerprint = \md5($invoice->filePlainContent);

        $invoice->signingMethod = $signingMethod;

        unlink($fileSignedPath);
        unlink($filePlainPath);

        if (empty($invoice->filePlainContent)) {
            throw new EFattureWsClientException("Openssl was unable to decrypt file.");
        }

        $invoice->invoiceData = new InvoiceData($invoice->getFilePlainContent());
        return $invoice;
    }

    public function getInvoiceData()
    {
        return $this->invoiceData;
    }

    public function getFilePlainContent()
    {
        return $this->filePlainContent;
    }

    public function getFileSignedContent()
    {
        return $this->fileSignedContent;
    }

    public function getFilePlainFingerprint()
    {
        return $this->filePlainFingerprint;
    }

    public function getFileSignedFingerprint()
    {
        return $this->fileSignedFingerprint;
    }

    public function getSigningMethod()
    {
        return $this->signingMethod;
    }
}

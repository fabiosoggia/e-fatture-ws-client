<?php

namespace CloudFinance\EFattureWsClient\V1\Invoice;

use CloudFinance\EFattureWsClient\V1\Invoice\InvoiceData;
use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;

define("SIGNING_METHOD_CADES_BES", "CAdES-BES");
define("SIGNING_METHOD_XADES_BES", "XAdES-BES");

class SignedInvoiceReader
{
    private $filePlainPath;
    private $filePlainFingerprint;

    private $fileSignedPath;
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

        $invoice->fileSignedPath = \tempnam(\sys_get_temp_dir(), "fsp");
        $invoice->filePlainPath = \tempnam(\sys_get_temp_dir(), "fpp");

        $res = \file_put_contents($invoice->fileSignedPath, $string);
        if ($res === false) {
            throw new EFattureWsClientException("Unable to write encryped file");
        }

        if ($signingMethod === "CAdES-BES") {
            $openSslCommand = "openssl smime -verify -noverify -in '%s' -inform DER -out '%s'";
            $openSslCommand = \sprintf($openSslCommand, $invoice->fileSignedPath, $invoice->filePlainPath);
            $res = \exec($openSslCommand);
        }

        if ($signingMethod === "XAdES-BES") {
            $res = \file_put_contents($invoice->filePlainPath, $string);
        }

        $invoice->filePlainFingerprint = \md5_file($invoice->filePlainPath);
        $invoice->fileSignedFingerprint = \md5_file($invoice->fileSignedPath);

        $invoice->signingMethod = $signingMethod;

        $invoice->invoiceData = new InvoiceData($invoice->getFilePlainContent());

        return $invoice;
    }

    function __destruct() {
        $this->clear();
    }

    public function clear()
    {
        if (\file_exists($this->fileSignedPath)) {
            \unlink($this->fileSignedPath);
            $this->fileSignedPath = null;
        }

        if (\file_exists($this->filePlainPath)) {
            \unlink($this->filePlainPath);
            $this->filePlainPath = null;
        }
    }

    public function getInvoiceData()
    {
        return $this->invoiceData;
    }

    public function getFilePlainContent()
    {
        return file_get_contents($this->filePlainPath);
    }

    public function getFileSignedContent()
    {
        return file_get_contents($this->fileSignedPath);
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

<?php

namespace CloudFinance\EFattureWsClient\V1\Invoice;

use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;
use CloudFinance\EFattureWsClient\V1\Invoice\InvoiceData;

class SignedInvoiceReader
{
    private $filePlainContent;
    private $filePlainFingerprint;

    private $fileSignedContent;
    private $fileSignedFingerprint;

    private $signingMethod;
    private $invoiceData;

    public const CAdES_BES = "CAdES-BES";
    public const XAdES_BES = "XAdES-BES";

    private function __construct() {
    }

    public static function getSigningMethodByFileName(string $fileName)
    {
        $fileName = \trim($fileName);

        if (preg_match('/.xml.p7m$/i', $fileName) === 1) {
            return self::CAdES_BES;
        }
        if (preg_match('/.xml$/i', $fileName) === 1) {
            return self::XAdES_BES;
        }
        return false;
    }

    public static function getFileExtensionBySigningMethod(string $signingMethod)
    {
        if ($signingMethod === self::CAdES_BES) {
            return "xml.p7m";
        }
        if ($signingMethod === self::XAdES_BES) {
            return "xml";
        }
        return false;
    }

    public static function removeXadESBESSignature(string $content)
    {
        $domDocument = new \DomDocument();
        $domDocument->loadXML($content);
        $nodes = $domDocument->getElementsByTagNameNS("http://www.w3.org/2000/09/xmldsig#", "Signature");
        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }
        return $domDocument->saveXML();
    }

    public static function createFromSignedString($signingMethod, $string)
    {
        if (!in_array($signingMethod, [ self::CAdES_BES, self::XAdES_BES ])) {
            throw new EFattureWsClientException("Field 'signingMethod' must be 'CAdES-BES' or 'XAdES-BES'.");
        }

        $invoice = new self;

        if ($signingMethod === self::CAdES_BES) {
            $fileSignedPath = \tempnam(\sys_get_temp_dir(), "fsp");
            $filePlainPath = \tempnam(\sys_get_temp_dir(), "fpp");

            if (($fileSignedPath === false) || ($filePlainPath === false)) {
                throw new EFattureWsClientException("Unable to create temporary files.");
            }

            $res = \file_put_contents($fileSignedPath, $string);
            if ($res === false) {
                throw new EFattureWsClientException("Unable to write temporary files.");
            }

            $openSslCommand = "openssl smime -verify -noverify -in '%s' -inform DER -out '%s'";
            $openSslCommand = \sprintf($openSslCommand, $fileSignedPath, $filePlainPath);
            $res = \exec($openSslCommand, $output);
            $invoice->filePlainContent = \file_get_contents($filePlainPath);
            unlink($filePlainPath);
            unlink($fileSignedPath);
        }

        if ($signingMethod === self::XAdES_BES) {
            $invoice->filePlainContent = self::removeXadESBESSignature($string);
        }

        $invoice->fileSignedContent = $string;
        $invoice->fileSignedFingerprint = \md5($invoice->fileSignedContent);

        $invoice->filePlainFingerprint = \md5($invoice->filePlainContent);

        $invoice->signingMethod = $signingMethod;

        if (empty($invoice->filePlainContent)) {
            throw new EFattureWsClientException("Openssl was unable to decrypt file.");
        }

        $invoice->invoiceData = InvoiceData::loadXML($invoice->getFilePlainContent());
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

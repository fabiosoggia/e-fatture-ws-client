<?php

namespace CloudFinance\EFattureWsClient\V1\Xml\XmlWrapperValidators;

use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapperValidator;
use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapper;

class SchemaValidator implements XmlWrapperValidator {

    private $schemaLocation;

    public function __construct($schemaLocation) {
        if (!is_string($schemaLocation)) {
            $givenType = (\is_object($schemaLocation)) ? get_class($schemaLocation) : gettype($schemaLocation);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $this->schemaLocation = $schemaLocation;
    }

    public function getErrors(XmlWrapper $xmlWrapper)
    {
        $internalErrorPreviousValue = \libxml_use_internal_errors(true);
        $schema = $this->schemaLocation;
        $domDocument = $xmlWrapper->getDomDocument();
        $nativeErrors = [];
        \libxml_clear_errors();
        if (!$domDocument->schemaValidate($schema)) {
            $nativeErrors = \libxml_get_errors();
        }
        \libxml_use_internal_errors($internalErrorPreviousValue);

        $errors = [];
        foreach ($nativeErrors as $nativeError) {
            $code = $nativeError->code;
            $message = $nativeError->message;
            $errors[$code] = $message;
        }
        return $errors;
    }
}
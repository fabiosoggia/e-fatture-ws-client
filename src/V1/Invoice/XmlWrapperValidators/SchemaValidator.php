<?php

namespace CloudFinance\EFattureWsClient\V1\Invoice\XmlWrapperValidators;

use CloudFinance\EFattureWsClient\V1\Invoice\XmlWrapperValidator;
use CloudFinance\EFattureWsClient\V1\Invoice\XmlWrapper;

class SchemaValidator implements XmlWrapperValidator {

    private $schemaLocation;

    public function __construct(string $schemaLocation) {
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
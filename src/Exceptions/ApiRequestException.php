<?php

namespace CloudFinance\EFattureWsClient\Exceptions;

use CloudFinance\EFattureWsClient\Exceptions\ApiExceptionInterface;
use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;

class ApiRequestException extends EFattureWsClientException implements ApiExceptionInterface {

    public function __construct($message, $code, Exception $previous = null) {
        parent::__construct($message, 0, $previous);
        $this->code = $code;
    }
}
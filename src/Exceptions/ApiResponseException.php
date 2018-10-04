<?php

namespace CloudFinance\EFattureWsClient\Exceptions;

use Exception;
use CloudFinance\EFattureWsClient\Exceptions\ApiExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ApiResponseException extends \GuzzleHttp\Exception\RequestException implements ApiExceptionInterface {

    public function __construct(
        $errorCode,
        $errorMessage,
        RequestInterface $request,
        ResponseInterface $response = null,
        Exception $previous = null,
        array $handlerContext = []
    ) {
        parent::__construct($errorMessage, $request, $response, $previous, $handlerContext);
        $this->code = $errorCode;
    }

}
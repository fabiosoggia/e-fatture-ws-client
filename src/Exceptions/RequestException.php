<?php

namespace CloudFinance\EFattureWsClient\Exceptions;

use CloudFinance\EFattureWsClient\Exceptions\EFattureException;

class RequestException extends \GuzzleHttp\Exception\RequestException implements EFattureWsException {

}
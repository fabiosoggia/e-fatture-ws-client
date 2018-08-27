<?php

namespace CloudFinance\EFattureWsClient;

require_once __DIR__ . "/../vendor/autoload.php";

use CloudFinance\EFattureWsClient\Exceptions\UnsupportedClientVersion;

class ClientBuilder
{
    public static function build(string $version = "1.0")
    {
        if ($version === "1.0") {
            return new \CloudFinance\EFattureWsClient\V1\Client();
        }

        $error = sprintf("'%s' is not a valid client version.");
        throw new UnsupportedClientVersion($error);
    }
}

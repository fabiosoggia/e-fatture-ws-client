<?php

namespace CloudFinance\EFattureWsClient;

use CloudFinance\EFattureWsClient\Exceptions\UnsupportedClientVersion;

class ClientBuilder
{
    const VERSION_1 = "1.0";

    public static function build($version)
    {
        if ($version === self::VERSION_1) {
            return new \CloudFinance\EFattureWsClient\V1\Client();
        }

        $error = sprintf("'%s' is not a valid client version.");
        throw new UnsupportedClientVersion($error);
    }
}

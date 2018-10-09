<?php

namespace CloudFinance\EFattureWsClient;

use CloudFinance\EFattureWsClient\Exceptions\UnsupportedClientVersion;

class ClientBuilder
{
    const VERSION_1 = "1.0";

    public static function build($version)
    {
        if (!is_string($version)) {
            $givenType = (\is_object($version)) ? get_class($version) : gettype($version);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

        if ($version === self::VERSION_1) {
            return new \CloudFinance\EFattureWsClient\V1\Client();
        }

        $error = sprintf("'%s' is not a valid client version.", $version);
        throw new UnsupportedClientVersion($error);
    }
}

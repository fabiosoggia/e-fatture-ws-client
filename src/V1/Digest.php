<?php

namespace CloudFinance\EFattureWsClient\V1;

class Digest
{
    private $digest;

    public function __construct($uuid, $privateKey, $payload) {
        $this->digest = self::create($uuid, $privateKey, $payload);
    }

    public static function create($uuid, $privateKey, $payload)
    {
        if (\is_array($payload)) {
            \ksort($payload);
        }
        $key = $uuid . ":" . $privateKey;
        $digest = \hash_hmac("sha256", json_encode($payload), $key);
        return $digest;
    }

    public function verify($digest)
    {
        if (function_exists("hash_equals")) {
            return \hash_equals($this->digest, $digest);
        }
        return $this->digest === $digest;
    }

    public function __toString()
    {
        return $this->digest;
    }
}

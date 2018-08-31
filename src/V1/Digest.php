<?php

namespace CloudFinance\EFattureWsClient\V1;

class Digest
{
    private $digest;

    public function __construct(string $uuid, string $privateKey, $payload) {
        $this->digest = self::create($uuid, $privateKey, $payload);
    }

    public static function create(string $uuid, string $privateKey, $payload)
    {
        if (\is_array($payload)) {
            \ksort($payload);
        }
        $key = $uuid . ":" . $privateKey;
        $digest = \hash_hmac("sha256", json_encode($payload), $key);
        return $digest;
    }

    public function verify(string $digest)
    {
        return \hash_equals($this->digest, $digest);
    }

    public function __toString()
    {
        return $this->digest;
    }
}

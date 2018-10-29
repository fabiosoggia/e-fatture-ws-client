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
        if (!is_string($uuid)) {
            $givenType = (\is_object($uuid)) ? get_class($uuid) : gettype($uuid);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        if (!is_string($privateKey)) {
            $givenType = (\is_object($privateKey)) ? get_class($privateKey) : gettype($privateKey);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 2, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        if (!is_string($payload)) {
            $givenType = (\is_object($payload)) ? get_class($payload) : gettype($payload);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 3, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $key = $uuid . ":" . $privateKey;
        $digest = \hash_hmac("sha256", $payload, $key);
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

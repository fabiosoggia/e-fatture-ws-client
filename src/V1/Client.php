<?php

namespace CloudFinance\EFattureWsClient\V1;

class Client
{
    private $uuid;
    private $privateKey;

    public function setUuid(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public function setPrivateKey(string $privateKey)
    {
        $this->privateKey = $privateKey;
    }
}

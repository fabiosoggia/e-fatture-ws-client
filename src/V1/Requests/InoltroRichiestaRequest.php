<?php

namespace CloudFinance\EFattureWsClient\V1\Requests;

interface InoltroRichiestaRequest
{
    public function getNomeFile(): string;
    public function getXml(): string;
    public function getPiva(): string;
    public function getExtra(): array;
}

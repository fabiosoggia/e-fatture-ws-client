<?php

namespace CloudFinance\EFattureWsClient\Tests;

use CloudFinance\EFattureWsClient\InvoiceBuilder;
use CloudFinance\EFattureWsClient\Iso3166;

class SkeletonClassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that true does in fact equal true
     */
    public function testEchoPhrase()
    {
        $builder = new InvoiceBuilder();
        $builder->set("/FatturaElettronica/FatturaElettronicaHeader[1]/DatiTrasmissione", "Ciao");
        $builder->set("/FatturaElettronica/FatturaElettronicaHeader[2]/DatiTrasmissione", "Fabio");
        echo $builder->saveXML();
    }
}

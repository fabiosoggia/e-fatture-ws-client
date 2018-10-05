<?php

namespace CloudFinance\EFattureWsClient\V1\Xml;

use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapper;

interface XmlWrapperValidator {

    /**
     * Valida l'xml. Restituisce l'array che utilizza come chiave il codice
     * dell'errore e come valore il messaggio dell'errore.
     *
     * @return array
     */
    public function getErrors(XmlWrapper $xmlWrapper);
}
<?php

namespace CloudFinance\EFattureWsClient\V1\Invoice;

use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;

define("MESSAGGI_1_0_NAMESPACE", "http://www.fatturapa.gov.it/sdi/messaggi/v1.0");

class NotificaEsito
{
    protected $domDocument;
    protected $rootNode;
    protected $domXPath;

    /**
     * Costruisci una nuova fattura. E' possibile passare al metodo una stringa
     * contenente la rappresentazione in XML della fattura per inizializzarne
     * il contenuto.
     *
     * NB: questa funzione effettua delle "normalizzazioni" sulla struttura
     *     dell'XML. Se l'XML in input non è stato creato da questa classe,
     *     l'XML prodotto potrebbe avere minime differenze rispetto da quello
     *     in input anche se non sono state effettuate set().
     *
     * @param string $xml
     */
    public function __construct(string $xml = null) {
        if (empty($xml)) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>
                <types:NotificaEsitoCommittente
                    xmlns:types="http://www.fatturapa.gov.it/sdi/messaggi/v1.0"
                    xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                    versione="1.0"
                    xsi:schemaLocation="http://www.fatturapa.gov.it/sdi/messaggi/v1.0 MessaggiTypes_v1.0.xsd ">
                </types:NotificaEsitoCommittente>';
        }

        $this->domDocument = new \DOMDocument();
        try {
            $this->domDocument->loadXML($xml);
        } catch (\Exception $ex) {
            $error = sprintf("Invalid notifica esito XML: %s.", $ex->getMessage());
            throw new EFattureWsClientException($error, $ex->getCode());
        }

        $this->rootNode = $this->domDocument->documentElement;
        $this->domXPath = new \DOMXPath($this->domDocument);

        if ($this->rootNode === null) {
            throw new EFattureWsClientException("Invalid notifica esito XML: missing root node.");
        }
        if ($this->rootNode->namespaceURI !== MESSAGGI_1_0_NAMESPACE) {
            throw new EFattureWsClientException("Invalid notifica esito XML: root node uses invalid namespace.");
        }
        if ($this->rootNode->localName !== "NotificaEsitoCommittente") {
            throw new EFattureWsClientException("Invalid notifica esito XML: root node is not NotificaEsitoCommittente.");
        }
    }

    /**
     * Questo metodo restituisce una stringa univoca che identifica la struttura
     * dell'XML. La stringa ottenuta è indipendente dalla posizione dei nodi
     * all'interno della struttura ad albero della fattura. As esempio, i
     * seguenti xml porteranno allo stesso risultato:
     *
     *         <radice>                     <radice>
     *             <a>Valore A</a>              <b>Valore B</b>
     *             <b>Valore B</b>              <a>Valore A</a>
     *         </radice>                    </radice>
     *
     * @return string
     */
    public function getFingerprint()
    {
        $this->normalize();

        $nodeList = $this->domXPath->query('//*[not(*)]');
        $nodes = [];
        foreach ($nodeList as $node) {
            $localPath = [
                $node->nodeName,
                $node->nodeValue
            ];
            $currentNode = $node->parentNode;
            $k = 0;
            while (!empty($currentNode) && ($k++ < 50)) {
                array_unshift($localPath, $currentNode->nodeName);
                $currentNode = $currentNode->parentNode;
            }

            $nodes[] = \strtolower(\implode("|", $localPath));
        }
        sort($nodes);
        $fingerprint = \md5(\json_encode($nodes));
        return $fingerprint;
    }

    /**
     * Valida la fattura costruita secondo lo standard di FatturaPA. Se la
     * fattura è corretta questa funzione termina senza restituire nulla,
     * altrimenti viene lanciata un'eccezione di tipo EFattureWsClientException.
     *
     * @throws CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException
     * @return void
     */
    public function validate()
    {
        $this->normalize();

        $internalErrorPreviousValue = libxml_use_internal_errors(true);
        $schema = __DIR__ . "/../../../resources/MessaggiTypes_v1.1.xsd";
        if ($this->domDocument->schemaValidate($schema)) {
            libxml_use_internal_errors($internalErrorPreviousValue);
            return;
        }

        $error = libxml_get_last_error();
        libxml_use_internal_errors($internalErrorPreviousValue);
        throw new EFattureWsClientException($error->message, $error->code);
    }

    /**
     * Si assicura che $path:
     *  - sia nel formato NotificaEsitoCommittente/aaa/bbb.
     *
     * @param string $path
     * @return void
     */
    private function normalizePath(string $path)
    {
        $path = "/" . $path . "/";
        $path = ltrim($path, "/");
        if ((strpos($path, "NotificaEsitoCommittente/") !== 0)) {
            $path = "NotificaEsitoCommittente/" . $path;
        }
        $path = \trim($path, "/");
        return $path;
    }

    private function retrieveNode(string $path, $createIfNotExists = false)
    {
        $parentNode = $this->rootNode;
        $currentPath = "/*";

        $path = \trim($path, "/");
        $tags = \explode('/', $path);

        $tagRegex = '/([a-zA-Z0-9]+)(\[(\d*)\])?/i';

        foreach ($tags as $token) {
            $res = preg_match($tagRegex, $token, $matches);
            if ($res === false) {
                throw \Exception("Invalid regex.");
            }

            if ($res === 0) {
                $error = sprintf("Could not parse path '%s'.", $token);
                throw new EFattureWsClientException($error);
            }

            $tag = $matches[1];
            $index = count($matches) === 4 ? intval($matches[3]) : 1;
            $index = \max($index, 1);

            if (empty($tag)) {
                continue;
            }

            if ($tag === 'NotificaEsitoCommittente') {
                continue;
            }

            $tempPath = $currentPath . "/" . $tag;
            $nodes = $this->domXPath->query($tempPath);
            $nodesCount = \count($nodes);

            if ($nodesCount > $index - 1) {
                $node = $nodes->item($index - 1);
            } else {
                if (!$createIfNotExists) {
                    return null;
                }

                for ($n = $nodesCount; $n < $index; $n++) {
                    $node = $this->domDocument->createElementNS(null, $tag);
                    $parentNode->appendChild($node);
                }
            }

            $currentPath = $tempPath . "[" . $index . "]";
            $parentNode = $node;
        }

        return $parentNode;
    }

    /**
     * Setta una proprietà della fattura. Il path della fattura segue il formato
     * specificato da:
     * http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2.1/Rappresentazione_tabellare_del_tracciato_FatturaPA_versione_1.2.1.pdf
     *
     * Es 1:
     * $builder->set("/NotificaEsitoCommittente/IdentificativoSdI", "000000");
     *
     * @param string $path
     * @param string $value
     * @return void
     */
    public function set(string $path, string $value)
    {
        $path = $this->normalizePath($path);
        $node = $this->retrieveNode($path, true);
        $node->nodeValue = \trim($value);
    }

    /**
     * Questo metodo restituisce true se è presente un contenuto per il $path
     * specificato, false altrimenti.
     *
     * @see set()
     * @param string $path
     * @return boolean
     */
    public function has(string $path)
    {
        if (empty($this->get($path))) {
            return false;
        }
        return true;
    }

    /**
     * Questo metodo restituisce il contenuto presente in $path. Se non è
     * presente nessun contenuto restituisce $default.
     *
     * @see set()
     * @param string $path
     * @param string $default
     * @return boolean
     */
    public function get(string $path, string $default = null)
    {
        $path = $this->normalizePath($path);
        $path = str_replace("NotificaEsitoCommittente/", "/*/", $path);
        $nodes = $this->domXPath->query($path);
        if (\count($nodes) === 0) {
            return $default;
        }
        $node = $nodes->item(0);
        $value = \trim($node->nodeValue);
        if (empty($value)) {
            return $default;
        }
        return $value;
    }

    public function normalize()
    {
        // Rimove tag vuoti.

        // Preso da:
        // https://stackoverflow.com/a/21492078
        //
        // not(*) does not have children elements
        // not(@*) does not have attributes
        // text()[normalize-space()] nodes that include whitespace text
        while (($node_list = $this->domXPath->query('//*[not(*) and not(@*) and not(text()[normalize-space()])]')) && $node_list->length) {
            foreach ($node_list as $node) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    /**
     * Restituisce l'XML della fattura come stringa.
     *
     * @param boolean $prettyPrint
     * @return string
     */
    public function saveXML($prettyPrint = false)
    {
        if ($prettyPrint) {
            $this->domDocument->preserveWhiteSpace = false;
            $this->domDocument->formatOutput = true;
        }

        $this->normalize();

        return $this->domDocument->saveXML(null, LIBXML_NOEMPTYTAG | LIBXML_NOBLANKS);
    }

    public function setIdentificativoSdi(string $indentificativoId)
    {
        $this->set("/NotificaEsitoCommittente/IdentificativoSdI", $indentificativoId);
    }

    public function setRiferimentoFattura(string $numeroFattura, int $annoFattura, int $posizioneFattura)
    {
        $this->set("/NotificaEsitoCommittente/RiferimentoFattura/NumeroFattura", $numeroFattura);
        $this->set("/NotificaEsitoCommittente/RiferimentoFattura/AnnoFattura", $annoFattura);
        $this->set("/NotificaEsitoCommittente/RiferimentoFattura/PosizioneFattura", $posizioneFattura);
    }

    public function setEsito(string $esito)
    {
        $this->set("/NotificaEsitoCommittente/Esito", $esito);
    }

    public function setDescrizione(string $descrizione)
    {
        $this->set("/NotificaEsitoCommittente/Descrizione", $descrizione);
    }

    public function setMessageIdCommittente(string $messageIdCommittente)
    {
        $this->set("/NotificaEsitoCommittente/MessageIdCommittente", $messageIdCommittente);
    }
}

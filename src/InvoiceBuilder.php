<?php

namespace CloudFinance\EFattureWsClient;

use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;
use CloudFinance\EFattureWsClient\Exceptions\InvalidInvoice;
use CloudFinance\EFattureWsClient\Exceptions\InvalidInvoiceParameter;
use CloudFinance\EFattureWsClient\Iso3166;

define("FATTURA_PA_1_2_NAMESPACE", "http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2");

class InvoiceBuilder
{
    protected $domDocument;
    protected $rootNode;
    protected $domXPath;
    protected $fatturaPaNamespacePrefix = "p:";

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
    public function __construct(string $xml = null, $normalize = false) {
        if (empty($xml)) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>
                <p:FatturaElettronica versione="FPA12"
                xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2"
                xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd" />';;
        }

        if ($normalize) {
            $xml = $this->normalizeXml($xml);
        }

        $this->domDocument = new \DOMDocument();
        try {
            $this->domDocument->loadXML($xml);
        } catch (\Exception $ex) {
            $error = sprintf("Invalid invoice XML: %s.", $ex->getMessage());
            throw new InvalidInvoice($error, $ex->getCode());
        }

        $this->rootNode = $this->domDocument->documentElement;
        $this->domXPath = new \DOMXPath($this->domDocument);

        if ($this->rootNode === null) {
            throw new InvalidInvoice("Invalid invoice XML: missing root node.");
        }
        if ($this->rootNode->namespaceURI !== FATTURA_PA_1_2_NAMESPACE) {
            throw new InvalidInvoice("Invalid invoice XML: root node uses invalid namespace.");
        }
        if ($this->rootNode->localName !== "FatturaElettronica") {
            throw new InvalidInvoice("Invalid invoice XML: root node is not FatturaElettronica.");
        }
        if ($normalize) {
            if (($this->rootNode->prefix . ":") !== $this->fatturaPaNamespacePrefix) {
                throw new InvalidInvoice("Invalid invoice XML: invalid namespace prefix for FatturaElettronica.");
            }
        }
    }

    private function normalizeXml(string $xml)
    {
        // Espandi nodi vuoti
        try {
            $domDocument = new \DOMDocument();
            $domDocument->loadXML($xml);
            $xml = $domDocument->saveXML(null, LIBXML_NOEMPTYTAG);
        } catch (\Exception $ex) {
        }

        // Mancano alcuni caratteri, vedere:
        // https://www.w3.org/TR/xml/#NT-Name
        $nameChar = 'a-z_\xC0-\xD6\xD8-\xF6\-\.0-9\xB7';

        // Rimuovi tutti prefissi dai tag di apertura con namespace
        $xml = preg_replace('/<[' . $nameChar . ']*:/i', '<', $xml);
        // Rimuovi tutti prefissi dai tag di chiusura con namespace
        $xml = preg_replace('/<\/[' . $nameChar . ']*:/i', '</', $xml);
        // Rimuovi tutti gli attributi dei tag
        $xml = preg_replace('/<([' . $nameChar . ']+)\s[^<]*/i', '<$1>', $xml);

        // Namespace di FatturaPA: tag di apertura
        $pattern = '/<FatturaElettronica>/';
        $replacement = '<' . $this->fatturaPaNamespacePrefix . 'FatturaElettronica versione="FPA12"
            xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:' . \trim($this->fatturaPaNamespacePrefix, ':') . '="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2"
            xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">';
        $xml = preg_replace($pattern, $replacement, $xml);

        // Namespace di FatturaPA: tag di chiusura
        $pattern = '/<\/FatturaElettronica>/';
        $replacement = '</' . $this->fatturaPaNamespacePrefix . 'FatturaElettronica>';
        $xml = preg_replace($pattern, $replacement, $xml);

        // Rimuovi spazi superflui
        $patterns = [ '/>\s+</', '/\s\s+/' ];
        $replacements = [ '><', ' ' ];
        $xml = preg_replace($patterns, $replacements, $xml);

        return $xml;
    }

    /**
     * Valida la fattura costruita secondo lo standard di FatturaPA. Se la
     * fattura è corretta questa funzione termina senza restituire nulla,
     * altrimenti viene lanciata un'eccezione di tipo InvalidInvoice.
     *
     * @throws CloudFinance\EFattureWsClient\Exceptions\InvalidInvoice
     * @return void
     */
    public function validate()
    {
        $this->normalize();

        $internalErrorPreviousValue = libxml_use_internal_errors(true);
        $schema = __DIR__ . "/../resources/Schema_del_file_xml_FatturaPA_versione_1.2.xsd";
        if ($this->domDocument->schemaValidate($schema)) {
            libxml_use_internal_errors($internalErrorPreviousValue);
            return;
        }

        $error = libxml_get_last_error();
        libxml_use_internal_errors($internalErrorPreviousValue);
        throw new InvalidInvoice($error->message, $error->code);
    }

    /**
     * Si assicura che $path:
     *  - sia nel formato FatturaElettronica/aaa/bbb.
     *
     * @param string $path
     * @return void
     */
    private function normalizePath(string $path)
    {
        $path = "/" . $path . "/";
        $path = ltrim($path, "/");
        if ((strpos($path, "FatturaElettronica/") !== 0)) {
            $path = "FatturaElettronica/" . $path;
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

            if ($tag === 'FatturaElettronica') {
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
     * $builder->set("/FatturaElettronica/FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice", "000000");
     *
     * Es 2:
     * $builder->set("/FatturaElettronica/FatturaElettronicaBody[1]/DatiGenerali/DatiGeneraliDocumento/Numero", "1");
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
        $path = str_replace("FatturaElettronica/", "/*/", $path);
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
}

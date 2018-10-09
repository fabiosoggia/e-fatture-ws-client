<?php

namespace CloudFinance\EFattureWsClient\V1\Xml;

use CloudFinance\EFattureWsClient\Exceptions\EFattureWsClientException;
use CloudFinance\EFattureWsClient\Exceptions\InvalidInvoice;
use CloudFinance\EFattureWsClient\Exceptions\InvalidXml;
use CloudFinance\EFattureWsClient\Exceptions\InvalidInvoiceParameter;
use CloudFinance\EFattureWsClient\Iso3166;
use CloudFinance\EFattureWsClient\V1\Xml\XmlWrapperValidator;
use \DOMDocument;
use \DOMXPath;

define("FATTURA_PA_1_2_NAMESPACE", "http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2");

class XmlWrapper
{
    protected $domDocument;
    protected $rootNode;
    protected $rootNodeTag;
    protected $domXPath;
    protected $validators;

    public function __construct(DOMDocument $domDocument) {
        $this->domDocument = $domDocument;
        $this->domXPath = new DOMXPath($this->domDocument);
        $this->rootNode = $this->domDocument->documentElement;
        $this->rootNodeTag = $this->rootNode->localName;
        $this->validators = [];

        if ($this->rootNode === null) {
            throw new EFattureWsClientException("Invalid DOMDocument: missing root node.");
        }
    }

    public function clone()
    {
        $self = new self($this->domDocument);
        return $self;
    }

    public function addValidator(XmlWrapperValidator $validator)
    {
        $this->validators[] = $validator;
        return $this;
    }

    public function getDomDocument()
    {
        return $this->domDocument;
    }

    public function getDomXPath()
    {
        return $this->domXPath;
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
     * Valida che l'xml costruito sia compatibile con i validator settati. Se
     * è corretto questa funzione termina senza restituire nulla, altrimenti
     * viene lanciata un'eccezione di tipo InvalidXml.
     *
     * @throws CloudFinance\EFattureWsClient\Exceptions\InvalidXml
     * @return void
     */
    public function validate()
    {
        $errors = $this->getErrors();
        if (count($errors) === 0) {
            return;
        }

        foreach ($errors as $errorCode => $errorMessage) {
            throw new InvalidXml($errorMessage, $errorCode);
        }
    }

    public function getErrors()
    {
        $this->normalize();

        $errors = [];
        foreach ($this->validators as $validator) {
            $errors += $validator->getErrors($this);
        }
        return $errors;
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
        if ((strpos($path, $this->rootNodeTag . "/") !== 0)) {
            $path = $this->rootNodeTag . "/" . $path;
        }
        $path = \trim($path, "/");
        return $path;
    }

    public function retrieveNode(string $path, $createIfNotExists = false)
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

            if ($tag === $this->rootNodeTag) {
                continue;
            }

            $tempPath = $currentPath . "/" . $tag;
            $nodes = $this->domXPath->query($tempPath);
            $nodesCount = $nodes->length;
            $node = null;

            if ($nodesCount > $index - 1) {
                $node = $nodes->item($index - 1);

                if ($node === null) {
                    $m = \sprintf("XmlWrapper: Index '%d' is not valid in path '%s' (%d).", $index - 1, $tempPath, $nodesCount);
                    throw new EFattureWsClientException($m);
                }

            } else {
                if (!$createIfNotExists) {
                    return null;
                }

                for ($n = $nodesCount; $n < $index; $n++) {
                    $node = $this->domDocument->createElementNS(null, $tag);

                    if ($node === false) {
                        $m = \sprintf("XmlWrapper: Unable to create <%s> at path \n%s\n%d", $tag, $tempPath, $n);
                        throw new EFattureWsClientException($m);
                    }

                    $parentNode->appendChild($node);
                }
            }

            $currentPath = $tempPath . "[" . $index . "]";
            $parentNode = $node;
        }

        return $parentNode;
    }

    public function getChildrenPaths(string $path)
    {
        $path = $this->normalizePath($path);
        $node = $this->retrieveNode($path, false);

        if ($node === null) {
            return [];
        }

        $paths = [];
        $childrens = $node->childNodes;
        $childrensCount = $childrens->length;
        for ($i = 0; $i < $childrensCount; $i++) {
            $child = $childrens->item($i);
            $childTag = $child->localName;
            $childPath = $path . "/" . $childTag;
            $paths[] = $childPath;
        }
        return $paths;
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

        if (empty($node)) {
            $m = \sprintf("XmlWrapper: Retrieved empty node for path %s", $path);
            throw new EFattureWsClientException($m);
        }

        $node->nodeValue = \trim($value);
        return $this;
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
        $path = str_replace($this->rootNodeTag . "/", "/*/", $path);
        $nodes = $this->domXPath->query($path);
        if ($nodes->length === 0) {
            return $default;
        }
        $node = $nodes->item(0);
        $value = \trim($node->nodeValue);
        if (empty($value)) {
            return $default;
        }
        return $value;
    }

    public function count(string $path)
    {
        $path = $this->normalizePath($path);
        $path = str_replace($this->rootNodeTag . "/", "/*/", $path);
        $nodes = $this->domXPath->query($path);
        return $nodes->length;
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
        return $this;
    }

    /**
     * Restituisce l'XML della fattura come stringa.
     *
     * @param boolean $prettyPrint
     * @return string
     */
    public function saveXML($prettyPrint = false)
    {
        $this->domDocument->preserveWhiteSpace = false;
        $this->domDocument->formatOutput = false;

        if ($prettyPrint) {
            $this->domDocument->formatOutput = true;
        }

        $this->normalize();

        return $this->domDocument->saveXML(null, LIBXML_NOEMPTYTAG | LIBXML_NOBLANKS);
    }

    public function __toString()
    {
        return $this->saveXML();
    }

    public function retrieveAttributeNode(string $path, string $attribute, $createIfNotExists = false)
    {
        $node = $this->retrieveNode($path, $createIfNotExists);
        $attributes = $node->attributes;
        $domAttribute = $attributes->getNamedItem($attribute);

        if ($domAttribute === null) {
            if ($createIfNotExists === false) {
                return null;
            }

            $domAttribute = $this->domDocument->createAttribute($attribute);
        }
        $node->appendChild($domAttribute);
        return $domAttribute;
    }

    public function setAttribute(string $path, string $attribute, string $value)
    {
        $domAttribute = $this->retrieveAttributeNode($path, $attribute, true);
        $domAttribute->value = $value;
        return $this;
    }

    public function getAttribute(string $path, string $attribute, string $default = null)
    {
        $domAttribute = $this->retrieveAttributeNode($path, $attribute, false);
        if ($domAttribute === null) {
            return $default;
        }
        return $domAttribute->value;
    }
}

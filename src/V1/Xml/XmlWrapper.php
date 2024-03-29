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

        if ($this->rootNode === null) {
            throw new EFattureWsClientException("Invalid DOMDocument: missing root node.");
        }

        $this->rootNodeTag = $this->rootNode->localName;
        $this->validators = [];
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

    public function getRootNodeTag()
    {
        return $this->rootNodeTag;
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
    public function getContentFingerprint()
    {
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

    public function getFingerprint()
    {
        $fingerprint = \md5(\strtolower($this->saveXML()));
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
        // $this->normalize();

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
    private function normalizePath($path)
    {
        $path = "/" . $path . "/";
        $path = ltrim($path, "/");
        if ((strpos($path, $this->rootNodeTag . "/") !== 0)) {
            $path = $this->rootNodeTag . "/" . $path;
        }
        $path = \trim($path, "/");
        return $path;
    }

    public function retrieveNode($path, $createIfNotExists = false)
    {
        if (!is_string($path)) {
            $givenType = (\is_object($path)) ? get_class($path) : gettype($path);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        if (!is_bool($createIfNotExists)) {
            $givenType = (\is_object($createIfNotExists)) ? get_class($createIfNotExists) : gettype($createIfNotExists);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 2, __METHOD__, "bool", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $parentNode = $this->rootNode;
        $currentPath = "/*";

        $path = \trim($path, "/");
        $tags = \explode('/', $path);

        $tagRegex = '/([a-zA-Z0-9]+)(\[(\d*)\])?/i';

        foreach ($tags as $token) {
            $res = preg_match($tagRegex, $token, $matches);
            if ($res === false) {
                throw new \Exception("Invalid regex.");
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

    public function getChildrenPaths($path)
    {
        if (!is_string($path)) {
            $givenType = (\is_object($path)) ? get_class($path) : gettype($path);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

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
    public function set($path, $value)
    {
        if (!is_string($path)) {
            $givenType = (\is_object($path)) ? get_class($path) : gettype($path);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        if (!is_string($value)) {
            $givenType = (\is_object($value)) ? get_class($value) : gettype($value);
            $message = "Argument %d passed to %s() for '%s' must be of the type %s, %s given";
            $message = sprintf($message, 2, __METHOD__, $path, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $path = $this->normalizePath($path);
        $node = $this->retrieveNode($path, true);

        if (empty($node)) {
            $m = \sprintf("XmlWrapper: Retrieved empty node for path %s", $path);
            throw new EFattureWsClientException($m);
        }

        $node->nodeValue = \htmlspecialchars(\trim($value));
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
    public function has($path)
    {
        if (!is_string($path)) {
            $givenType = (\is_object($path)) ? get_class($path) : gettype($path);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

        if ($this->get($path) === null) {
            return false;
        }
        return true;
    }

    public function hasValue($path)
    {
        return $this->has($path);
    }

    public function hasNode($path)
    {
        if (!is_string($path)) {
            $givenType = (\is_object($path)) ? get_class($path) : gettype($path);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $path = $this->normalizePath($path);
        $node = $this->retrieveNode($path, false);

        if ($node === null) {
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
    public function get($path, $default = null)
    {
        if (!is_string($path)) {
            $givenType = (\is_object($path)) ? get_class($path) : gettype($path);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $path = $this->normalizePath($path);
        $path = str_replace($this->rootNodeTag . "/", "/*/", $path);
        $nodes = $this->domXPath->query($path);
        if ($nodes->length === 0) {
            return $default;
        }
        $node = $nodes->item(0);
        $value = \trim($node->nodeValue);
        if (is_numeric($value)) {
            return $value;
        }
        if (empty($value)) {
            return $default;
        }
        return $value;
    }

    public function count($path)
    {
        if (!is_string($path)) {
            $givenType = (\is_object($path)) ? get_class($path) : gettype($path);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $path = $this->normalizePath($path);
        $path = str_replace($this->rootNodeTag . "/", "/*/", $path);
        $nodes = $this->domXPath->query($path);
        return $nodes->length;
    }

    /**
     * Rimuove i tag vuoti dall'XML.
     *
     * @return this
     */
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

        // $this->normalize();

        return $this->domDocument->saveXML(null, LIBXML_NOEMPTYTAG | LIBXML_NOBLANKS);
    }

    public function __toString()
    {
        return $this->saveXML();
    }

    public function retrieveAttributeNode($path, $attribute, $createIfNotExists = false)
    {
        if (!is_string($path)) {
            $givenType = (\is_object($path)) ? get_class($path) : gettype($path);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        if (!is_string($attribute)) {
            $givenType = (\is_object($attribute)) ? get_class($attribute) : gettype($attribute);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 2, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        if (!is_bool($createIfNotExists)) {
            $givenType = (\is_object($createIfNotExists)) ? get_class($createIfNotExists) : gettype($createIfNotExists);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 3, __METHOD__, "bool", $givenType);
            throw new \InvalidArgumentException($message);
        }

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

    public function setAttribute($path, $attribute, $value)
    {
        if (!is_string($path)) {
            $givenType = (\is_object($path)) ? get_class($path) : gettype($path);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        if (!is_string($attribute)) {
            $givenType = (\is_object($attribute)) ? get_class($attribute) : gettype($attribute);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 2, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        if (!is_string($value)) {
            $givenType = (\is_object($value)) ? get_class($value) : gettype($value);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 3, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $domAttribute = $this->retrieveAttributeNode($path, $attribute, true);
        $domAttribute->value = $value;
        return $this;
    }

    public function getAttribute($path, $attribute, $default = null)
    {
        if (!is_string($path)) {
            $givenType = (\is_object($path)) ? get_class($path) : gettype($path);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 1, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }
        if (!is_string($attribute)) {
            $givenType = (\is_object($attribute)) ? get_class($attribute) : gettype($attribute);
            $message = "Argument %d passed to %s() must be of the type %s, %s given";
            $message = sprintf($message, 2, __METHOD__, "string", $givenType);
            throw new \InvalidArgumentException($message);
        }

        $domAttribute = $this->retrieveAttributeNode($path, $attribute, false);
        if ($domAttribute === null) {
            return $default;
        }
        return $domAttribute->value;
    }

    /**
     * Restituisce il (x)path di un nodo.
     *
     * @param DOMNode $node
     * @return void
     */
    public function getNodePath($node)
    {
        $ancestors = $this->domXPath->query("ancestor::*", $node);
        $nodePath = "";
        for ($j = 0; $j < $ancestors->length + 1; $j++) {
            $ancestor = ($j < $ancestors->length) ? $ancestors->item($j) : $node;
            $ancestorTag = $ancestor->localName;
            $n = $this->domXPath->evaluate("count(preceding-sibling::$ancestorTag)", $ancestor) + 1;
            $n = intval($n);
            $nodePath = $nodePath . "/$ancestorTag";
            if ($n > 1) {
                $nodePath = $nodePath . "[$n]";
            }
        }
        return $nodePath;
    }

    /**
     * Restituisci i dati dell'xml come array associativo di path => valore.
     *
     * @return array
     */
    public function toArray()
    {
        $data = [];
        $leafs = $this->domXPath->query("//*[not(*)]");
        for ($i = 0; $i < $leafs->length; $i++) {
            $leaf = $leafs->item($i);
            // TODO: getNodePath() potrebbe restituire dei path inattesi
            // $leafPath = $leaf->getNodePath();
            // $leafPath = preg_replace('/[^\/]*:/', '', $leafPath);
            $leafPath = $this->getNodePath($leaf);
            $data[$leafPath] = $this->get($leafPath);
        }

        return $data;
    }
}

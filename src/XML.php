<?php
/**
 * This file is part of the Essence library.
 *
 * @author     Quetzy Garcia <quetzyg@impensavel.com>
 * @copyright  2014-2016
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace Impensavel\Essence;

use DOMDocument;
use DOMException;
use DOMNode;
use DOMNodeList;
use DOMText;
use DOMXPath;
use LibXMLError;
use SplFileInfo;
use XMLReader;

class XML extends AbstractEssence
{
    /**
     * XML Reader object
     *
     * @access  protected
     * @var     XMLReader
     */
    protected $reader;

    /**
     * DOM Document object
     *
     * @access  protected
     * @var     DOMDocument
     */
    protected $doc;

    /**
     * DOM XPath object
     *
     * @access  protected
     * @var     DOMXPath
     */
    protected $element;

    /**
     * Registered Element data
     *
     * @access  protected
     * @var     array
     */
    protected $data = array();

    /**
     * Current Element XPath stack
     *
     * @access  protected
     * @var     array
     */
    protected $stack = array();

    /**
     * Current Element XPath
     *
     * @access  protected
     * @var     string
     */
    protected $current;

    /**
     * Skip to Element XPath
     *
     * @access  protected
     * @var     string
     */
    protected $skip;

    /**
     * XML constructor
     *
     * @access  public
     * @param   array  $elements   Elements
     * @param   array  $namespaces Namespaces
     * @throws  EssenceException
     * @return  XML
     */
    public function __construct(array $elements, array $namespaces = array())
    {
        foreach ($elements as $key => $element) {
            $this->register($element, trim($key, '/'));
        }

        $this->reader = new XMLReader();
        $this->doc = new DOMDocument();
        $this->element = new DOMXPath($this->doc);

        // register namespaces
        foreach ($namespaces as $prefix => $uri) {
            $this->element->registerNamespace($prefix, $uri);
        }

        // manually handle libXML errors
        libxml_use_internal_errors(true);
    }

    /**
     * Free resources
     *
     * @access  public
     * @return  void
     */
    public function __destruct()
    {
        $this->reader->close();
    }

    /**
     * Get the current node
     *
     * @access  protected
     * @throws  EssenceException
     * @return  DOMNode
     */
    protected function getCurrentNode()
    {
        // clear the libXML error buffer
        libxml_clear_errors();

        $node = @$this->reader->expand();

        $error = libxml_get_last_error();

        if ($error instanceof LibXMLError) {
            // only throw exceptions when level is ERROR or FATAL
            if ($error->level > LIBXML_ERR_WARNING) {
                throw new EssenceException(sprintf('%s @ line #%d [%s]', trim($error->message), $error->line, $this->current), $error->code);
            }
        }

        try {
            return $this->doc->importNode($node, true);
        } catch (DOMException $e) {
            throw new EssenceException('Node import failed', 0, $e);
        }
    }

    /**
     * Read the next Element and handle skipping
     *
     * @access  protected
     * @return  bool
     */
    protected function nextElement()
    {
        do {
            if (! $this->reader->read()) {
                return false;
            }

            // pop previous levels from the stack
            $this->stack = array_slice($this->stack, 0, $this->reader->depth, true);

            // push the current Element to the stack
            $this->stack[] = $this->reader->name;

            // update the current Element XPath
            $this->current = implode('/', $this->stack);

            // skip to Element
            $this->skip = ($this->skip == $this->current) ? null : $this->skip;
        } while ($this->skip !== null);

        return true;
    }

    /**
     * Check if an Element XPath is mapped
     *
     * @access  protected
     * @param   string  $xpath Element XPath
     * @return  bool
     */
    protected function isMapped($xpath)
    {
        $xpath = trim($xpath, '/');

        return isset($this->maps[$xpath]);
    }

    /**
     * Get registered Element data
     *
     * @access  protected
     * @param   string  $xpath Element XPath
     * @throws  EssenceException
     * @return  mixed
     */
    protected function getData($xpath)
    {
        $xpath = trim($xpath, '/');

        if (isset($this->data[$xpath])) {
            return $this->data[$xpath];
        }

        throw new EssenceException('Unregistered Element XPath: "/'.$xpath.'"');
    }

    /**
     * Prepare data for extraction
     *
     * @access  protected
     * @param   mixed  $input  Input data
     * @param   array  $config Configuration settings
     * @throws  EssenceException
     * @return  void
     */
    protected function prepare($input, array $config)
    {
        if ($input instanceof SplFileInfo) {
            if (@$this->reader->open($input->getPathname(), $config['encoding'], $config['options'])) {
                return;
            }

            throw new EssenceException('Could not open "'.$input->getPathname().'" for parsing');
        }

        if (is_string($input)) {
            if ($this->reader->XML($input, $config['encoding'], $config['options'])) {
                return;
            }

            throw new EssenceException('Could not set the XML input string for parsing');
        }

        if (is_resource($input)) {
            $type = get_resource_type($input);

            if ($type != 'stream') {
                throw new EssenceException('Invalid resource type: '.$type);
            }

            $string = stream_get_contents($input);

            if ($string === false) {
                throw new EssenceException('Failed to read input from stream');
            }

            $this->prepare($string, $config);

            return;
        }

        throw new EssenceException('Invalid input type: '.gettype($input));
    }

    /**
     * Count the children of a DOMNode
     *
     * @static
     * @access  protected
     * @param   DOMNode $node
     * @return  int
     */
    protected static function DOMNodeChildCount(DOMNode $node)
    {
        $count = 0;

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                if ($child->nodeType == XML_ELEMENT_NODE) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Get the DONNode attributes
     *
     * @static
     * @access  protected
     * @param   DOMNode   $node
     * @return  array
     */
    protected static function DOMNodeAttributes(DOMNode $node)
    {
        $attributes = array();

        foreach ($node->attributes as $attribute) {
            $attributes[$attribute->name] = $attribute->value;
        }

        return $attributes;
    }

    /**
     * Get the DOMNode value
     *
     * @static
     * @access  protected
     * @param   DOMNode $node
     * @param   bool    $associative Return associative array?
     * @param   bool    $attributes  Include node attributes?
     * @return  mixed
     */
    protected static function DOMNodeValue(DOMNode $node, $associative = false, $attributes = false)
    {
        // return the value immediately when we're dealing with a leaf
        // node without attributes or we simply don't want them included
        if (static::DOMNodeChildCount($node) == 0 && ($node->hasAttributes() === false || $attributes === false)) {
            return $node->nodeValue;
        }

        $children = array();

        if ($node->hasAttributes() && $attributes) {
            $children['@'] = static::DOMNodeAttributes($node);
        }

        foreach ($node->childNodes as $child) {
            // skip whitespace text nodes
            if ($child instanceof DOMText && $child->isWhitespaceInElementContent()) {
                continue;
            }

            if (static::DOMNodeChildCount($child) > 0) {
                $value = static::DOMNodeValue($child, $associative);
            } else {
                $value = $child->nodeValue;
            }

            if ($associative) {
                $children[$child->nodeName][] = $value;
            } else {
                $children[] = $value;
            }
        }

        return $children;
    }

    /**
     * Convert a DOMNodeList into an Array
     *
     * @static
     * @access  public
     * @param   DOMNodeList $nodeList
     * @param   bool        $associative Return associative array?
     * @param   bool        $attributes  Include node attributes?
     * @return  array
     */
    public static function DOMNodeListToArray(DOMNodeList $nodeList, $associative = false, $attributes = false)
    {
        $nodes = array();

        foreach ($nodeList as $node) {
            $nodes[] = static::DOMNodeValue($node, $associative, $attributes);
        }

        return $nodes;
    }

    /**
     * {@inheritdoc}
     */
    public function extract($input, array $config = array(), &$data = null)
    {
        $config = array_replace_recursive(array(
            'encoding' => 'UTF-8',
            'options'  => LIBXML_PARSEHUGE,
        ), $config);

        $this->prepare($input, $config);

        while ($this->nextElement()) {
            if (! $this->reader->isEmptyElement && $this->reader->nodeType === XMLReader::ELEMENT && $this->isMapped($this->current)) {
                $node = $this->getCurrentNode();

                // current element properties
                $properties = array();

                foreach ($this->maps[$this->current] as $key => $xpath) {
                    $xpath = trim($xpath);

                    // get registered Element data
                    if (strpos($xpath, '#') === 0) {
                        $properties[$key] = $this->getData(substr($xpath, 1));

                    // get evaluated XPath data
                    } else {
                        $properties[$key] = $this->element->evaluate($xpath, $node);

                        if ($properties[$key] === false) {
                            throw new EssenceException('Invalid XPath expression: "'.$xpath.'"');
                        }
                    }
                }

                // execute element data handler
                $arguments = array(
                    '/'.$this->current,
                    $properties,
                    &$data,
                );

                $result = call_user_func_array($this->handlers[$this->current], $arguments);

                if ($result) {
                    // skip to Element
                    if ($this->isMapped($result)) {
                        $this->skip = $result;

                    // store Element data
                    } else {
                        $this->data[$this->current] = $result;
                    }
                }
            }
        }

        return true;
    }
}

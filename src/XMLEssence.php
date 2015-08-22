<?php
/**
 * This file is part of the Essence library.
 *
 * @author     Quetzy Garcia <quetzyg@impensavel.com>
 * @copyright  2014-2015
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace Impensavel\Essence;

use DOMDocument;
use DOMException;
use DOMNode;
use DOMXPath;
use LibXMLError;
use SplFileInfo;
use XMLReader;

class XMLEssence extends AbstractEssence
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
     * XMLEssence constructor
     *
     * @access  public
     * @param   array  $elements   Elements
     * @param   array  $namespaces Namespaces
     * @throws  EssenceException
     * @return  XMLEssence
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
    protected function provision($input, array $config)
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

            $this->provision($string, $config);

            return;
        }

        throw new EssenceException('Invalid input type: '.gettype($input));
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

        $this->provision($input, $config);

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

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

use SoapClient;
use SoapFault;

class SOAPEssence extends XMLEssence
{
    /**
     * SOAP Client object
     *
     * @access  protected
     * @var     SoapClient
     */
    protected $client;

    /**
     * Last SOAP request
     *
     * @access  protected
     * @var     string
     */
    protected $lastRequest;

    /**
     * Last SOAP response
     *
     * @access  protected
     * @var     string
     */
    protected $lastResponse;

    /**
     * Last SOAP response headers
     *
     * @access  protected
     * @var     array
     */
    protected $lastResponseHeaders = array();

    /**
     * SOAP Essence constructor
     *
     * @access  public
     * @param   array  $elements   Elements
     * @param   string $wsdl       WSDL file URI
     * @param   array  $namespaces Namespaces
     * @param   array  $options    SOAP client options
     * @throws  EssenceException
     * @return  SOAPEssence
     */
    public function __construct(array $elements, $wsdl = null, array $namespaces = array(), array $options = array())
    {
        parent::__construct($elements, $namespaces);

        $options = array_merge($options, array(
            'exceptions' => true,
            'trace'      => true,
        ));

        try {
            $this->client = @new SoapClient($wsdl, $options);
        } catch (SoapFault $e) {
            throw new EssenceException('SOAP client could not be instantiated', 0, $e);
        }
    }

    /**
     * Return the last SOAP request
     *
     * @access  public
     * @return  string
     */
    public function lastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * Return the last SOAP response
     *
     * @access  public
     * @return  string
     */
    public function lastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Return the last SOAP response headers
     *
     * @access  public
     * @return  array
     */
    public function lastResponseHeaders()
    {
        return $this->lastResponseHeaders;
    }

    /**
     * {@inheritdoc}
     */
    public function extract($input, array $config = array(), &$data = null)
    {
        if (! is_array($input)) {
            throw new EssenceException('The input must be an associative array');
        }

        $input = array_replace_recursive(array(
            'function' => null,
        ), $input, array(
            'arguments' => array(),
            'options'   => array(),
            'headers'   => array(),
        ));

        if (empty($input['function'])) {
            throw new EssenceException('The SOAP function is not set');
        }

        try {
            $this->client->__soapCall(
                $input['function'],
                array($input['arguments']),
                $input['options'],
                $input['headers'],
                $this->lastResponseHeaders
            );

            $this->lastRequest = $this->client->__getLastRequest();
            $this->lastResponse = $this->client->__getLastResponse();

            return parent::extract($this->lastResponse, $config, $data);
        } catch (SoapFault $e) {
            throw new EssenceException($e->getMessage(), $e->getCode(), $e);
        }
    }
}

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
     * @access  private
     * @var     SoapClient
     */
    private $client;

    /**
     * Last SOAP request data
     *
     * @access  private
     * @var     array
     */
    private $lastRequest = array();

    /**
     * Last response
     *
     * @access  private
     * @var     string
     */
    private $lastResponse;

    /**
     * SOAPEssence constructor
     *
     * @access  public
     * @param   array  $elements Elements
     * @param   string $wsdl     WSDL file URI
     * @param   array  $config   SOAP client configuration
     * @throws  EssenceException
     * @return  SOAPEssence
     */
    public function __construct(array $elements, $wsdl = null, array $config = array())
    {
        parent::__construct($elements);

        $config = array_merge($config, array(
            'exceptions' => true,
            'trace'      => true,
        ));

        try {
            $this->client = new SoapClient($wsdl, $config);
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
     * {@inheritdoc}
     */
    public function extract($input, array $config = array(), $extra = null)
    {
        if (! is_array($input)) {
            throw new EssenceException('The input must be an associative array');
        }

        $input = array_replace_recursive(array(
            'function'  => null,
            'arguments' => array(),
        ), $input);

        if (empty($input['function'])) {
            throw new EssenceException('The SOAP function is not set');
        }

        try {
            $this->client->__soapCall($input['function'], array($input['arguments']));

            $this->lastRequest = $this->client->__getLastRequest();
            $this->lastResponse = $this->client->__getLastResponse();

            return parent::extract($this->lastResponse, $config, $extra);

        } catch (SoapFault $e) {
            throw new EssenceException($e->getMessage(), $e->getCode(), $e);
        }
    }
}

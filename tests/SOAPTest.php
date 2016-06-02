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

namespace Impensavel\Essence\Tests;

use PHPUnit_Framework_TestCase;

use Impensavel\Essence\SOAP;

class SOAPTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test instantiation to FAIL (missing URI in nonWSDL)
     *
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage SOAP client could not be instantiated
     *
     * @access  public
     * @return  void
     */
    public function testInstantiationFailMapMustBeArray()
    {
        new SOAP(array(
            '/Foo/Bar' => array(
                'map'     => array(),
                'handler' => function ($element, array $properties, &$data) {
                    // ...
                },
            ),
        ));
    }

    /**
     * Test instantiation to PASS
     *
     * @access  public
     * @return  SOAP
     */
    public function testInstantiationPass()
    {
        $elements = array(
            '/Foo/Bar' => array(
                'map'     => array(),
                'handler' => function ($element, array $properties, &$data) {
                    // ...
                },
            ),
        );

        $namespaces = array(
            'ns' => 'http://foo.bar/baz',
        );

        $options = array(
            'uri'      => 'foo',
            'location' => 'bar',
        );

        $essence = new SOAP($elements, null, $namespaces, $options);

        $this->assertInstanceOf('\Impensavel\Essence\SOAP', $essence);

        return $essence;
    }

    /**
     * Test extract() method to FAIL (invalid input)
     *
     * @depends                  testInstantiationPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage The input must be an associative array
     *
     * @access  public
     * @param   SOAP   $essence
     * @return  void
     */
    public function testExtractFailInvalidInput(SOAP $essence)
    {
        $essence->extract(true);
    }

    /**
     * Test extract() method to FAIL (function not set)
     *
     * @depends                  testInstantiationPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage The SOAP function is not set
     *
     * @access  public
     * @param   SOAP   $essence
     * @return  void
     */
    public function testExtractFailFunctionNotSet(SOAP $essence)
    {
        $essence->extract(array());
    }

    /**
     * Test extract() method to FAIL (invalid WSDL)
     *
     * @depends           testInstantiationPass
     * @expectedException \Impensavel\Essence\EssenceException
     *
     * @access  public
     * @param   SOAP   $essence
     * @return  void
     */
    public function testExtractFailInvalidURL(SOAP $essence)
    {
        $essence->extract(array(
            'function' => 'baz',
        ));
    }
}

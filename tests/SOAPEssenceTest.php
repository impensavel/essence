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

use Impensavel\Essence\SOAPEssence;

class SOAPEssenceTest extends PHPUnit_Framework_TestCase
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
        new SOAPEssence(array(
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
     * @return  SOAPEssence
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

        $essence = new SOAPEssence($elements, null, $namespaces, $options);

        $this->assertInstanceOf('\Impensavel\Essence\SOAPEssence', $essence);

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
     * @param   SOAPEssence $essence
     * @return  void
     */
    public function testExtractFailInvalidInput(SOAPEssence $essence)
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
     * @param   SOAPEssence $essence
     * @return  void
     */
    public function testExtractFailFunctionNotSet(SOAPEssence $essence)
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
     * @param   SOAPEssence $essence
     * @return  void
     */
    public function testExtractFailInvalidURL(SOAPEssence $essence)
    {
        $essence->extract(array(
            'function' => 'baz',
        ));
    }
}

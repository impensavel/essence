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

use PHPUnit_Framework_TestCase;

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
                'map'      => array(),
                'callback' => function () {},
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
        $essence = new SOAPEssence(array(
            '/Foo/Bar' => array(
                'map'      => array(),
                'callback' => function () {},
            ),
        ), null, array(
            'uri'      => 'foo',
            'location' => 'bar',
        ));

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
     * Test extract() method to FAIL (invalid URL)
     *
     * @depends                  testInstantiationPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage Unable to parse URL
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

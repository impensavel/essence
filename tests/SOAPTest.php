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

use Mockery;
use PHPUnit_Framework_TestCase;

use Impensavel\Essence\SOAP;

class SOAPTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test input file to PASS (readability)
     *
     * @return  array
     */
    public function testInputFilesPass()
    {
        $files = array(
            'response' => __DIR__.'/input/soap/response.xml',
        );

        foreach ($files as $file) {
            $this->assertTrue(is_readable($file));
        }

        return $files;
    }

    /**
     * Test instantiation to FAIL (missing URI in nonWSDL)
     *
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage SOAP client could not be instantiated
     *
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
     * Test instantiation (original) to PASS
     *
     * @return  SOAP
     */
    public function testInstantiationOriginalPass()
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
     * Test instantiation (mocked) to PASS
     *
     * @depends testInputFilesPass
     *
     * @param   array $files
     * @return  SOAP
     */
    public function testInstantiationMockPass(array $files)
    {
        $elements = array(
            '/soap:Envelope/soap:Body/m:ListOfCountryNamesByCodeResponse/m:ListOfCountryNamesByCodeResult/m:tCountryCodeAndName' => array(
                'map'     => array(
                    'code' => 'string(m:sISOCode)',
                    'name' => 'string(m:sName)',
                ),
                'handler' => function ($element, array $properties, &$data) {
                    $data[] = $properties;
                },
            ),
        );

        $namespaces = array(
            'm' => 'http://www.oorsprong.org/websamples.countryinfo',
        );

        $response = file_get_contents($files['response']);

        $essence = Mockery::mock('\Impensavel\Essence\SOAP[makeCall]', array(
            $elements,
            'http://webservices.oorsprong.org/websamples.countryinfo/CountryInfoService.wso?WSDL',
            $namespaces
        ));

        $essence->shouldReceive('makeCall')->twice()->andReturn($response);

        $this->assertInstanceOf('\Impensavel\Essence\SOAP', $essence);

        return $essence;
    }

    /**
     * Test extract() method to FAIL (invalid input)
     *
     * @depends                  testInstantiationOriginalPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage The input must be an associative array
     *
     * @param   SOAP $essence
     * @return  void
     */
    public function testExtractFailInvalidInput(SOAP $essence)
    {
        $essence->extract(true);
    }

    /**
     * Test extract() method to FAIL (function not set)
     *
     * @depends                  testInstantiationOriginalPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage The SOAP function is not set
     *
     * @param   SOAP $essence
     * @return  void
     */
    public function testExtractFailFunctionNotSet(SOAP $essence)
    {
        $essence->extract(array());
    }

    /**
     * Test extract() method to FAIL (invalid WSDL)
     *
     * @depends           testInstantiationOriginalPass
     * @expectedException \Impensavel\Essence\EssenceException
     *
     * @param   SOAP $essence
     * @return  void
     */
    public function testExtractFailInvalidURL(SOAP $essence)
    {
        $essence->extract(array(
            'function' => 'baz',
        ));
    }

    /**
     * Test extract() method to PASS
     *
     * @depends testInstantiationMockPass
     *
     * @param   SOAP $essence
     * @return  void
     */
    public function testExtractPass(SOAP $essence)
    {
        $countries = array();

        $input = array(
            'function' => 'ListOfCountryNamesByCode',
        );

        $essence->extract($input, array(), $countries);

        $this->assertCount(240, $countries);
    }

    /**
     * Test dump() method to PASS
     *
     * @depends testInstantiationMockPass
     *
     * @param   SOAP $essence
     * @return  void
     */
    public function testDumpPass(SOAP $essence)
    {
        $input = array(
            'function' => 'ListOfCountryNamesByCode',
        );

        $paths = $essence->dump($input);

        // XPaths
        $this->assertArrayHasKey('soap:Envelope', $paths);
        $this->assertArrayHasKey('soap:Envelope/soap:Body', $paths);
        $this->assertArrayHasKey('soap:Envelope/soap:Body/m:ListOfCountryNamesByCodeResponse', $paths);
        $this->assertArrayHasKey('soap:Envelope/soap:Body/m:ListOfCountryNamesByCodeResponse/m:ListOfCountryNamesByCodeResult', $paths);
        $this->assertArrayHasKey('soap:Envelope/soap:Body/m:ListOfCountryNamesByCodeResponse/m:ListOfCountryNamesByCodeResult/m:tCountryCodeAndName', $paths);
        $this->assertArrayHasKey('soap:Envelope/soap:Body/m:ListOfCountryNamesByCodeResponse/m:ListOfCountryNamesByCodeResult/m:tCountryCodeAndName/m:sISOCode', $paths);
        $this->assertArrayHasKey('soap:Envelope/soap:Body/m:ListOfCountryNamesByCodeResponse/m:ListOfCountryNamesByCodeResult/m:tCountryCodeAndName/m:sName', $paths);

        // Element count
        $this->assertEquals(1, $paths['soap:Envelope']);
        $this->assertEquals(1, $paths['soap:Envelope/soap:Body']);
        $this->assertEquals(1, $paths['soap:Envelope/soap:Body/m:ListOfCountryNamesByCodeResponse']);
        $this->assertEquals(1, $paths['soap:Envelope/soap:Body/m:ListOfCountryNamesByCodeResponse/m:ListOfCountryNamesByCodeResult']);
        $this->assertEquals(240, $paths['soap:Envelope/soap:Body/m:ListOfCountryNamesByCodeResponse/m:ListOfCountryNamesByCodeResult/m:tCountryCodeAndName']);
        $this->assertEquals(240, $paths['soap:Envelope/soap:Body/m:ListOfCountryNamesByCodeResponse/m:ListOfCountryNamesByCodeResult/m:tCountryCodeAndName/m:sISOCode']);
        $this->assertEquals(240, $paths['soap:Envelope/soap:Body/m:ListOfCountryNamesByCodeResponse/m:ListOfCountryNamesByCodeResult/m:tCountryCodeAndName/m:sName']);
    }
}

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

use PHPUnit_Framework_MockObject_MockBuilder as MockBuilder;
use PHPUnit_Framework_TestCase as TestCase;

use Impensavel\Essence\SOAP;

class SOAPTest extends TestCase
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
     * Test instantiation to PASS
     *
     * @return  MockBuilder
     */
    public function testInstantiationPass()
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

        $mockBuilder = $this->getMockBuilder('\Impensavel\Essence\SOAP')->setConstructorArgs(array(
            $elements,
            'http://webservices.oorsprong.org/websamples.countryinfo/CountryInfoService.wso?WSDL',
            $namespaces,
        ));

        $this->assertInstanceOf('\Impensavel\Essence\SOAP', $mockBuilder->getMock());

        return $mockBuilder;
    }

    /**
     * Test extract() method to FAIL (invalid input)
     *
     * @depends                  testInstantiationPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage The input must be an associative array
     *
     * @param   MockBuilder $mockBuilder
     * @return  void
     */
    public function testExtractFailInvalidInput(MockBuilder $mockBuilder)
    {
        // Do not replace methods
        $essence = $mockBuilder->setMethods(null)->getMock();

        $essence->extract(true);
    }

    /**
     * Test extract() method to FAIL (function not set)
     *
     * @depends                  testInstantiationPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage The SOAP function is not set
     *
     * @param   MockBuilder $mockBuilder
     * @return  void
     */
    public function testExtractFailFunctionNotSet(MockBuilder $mockBuilder)
    {
        // Do not replace methods
        $essence = $mockBuilder->setMethods(null)->getMock();

        $essence->extract(array());
    }

    /**
     * Test extract() method to FAIL (invalid WSDL)
     *
     * @depends           testInstantiationPass
     * @expectedException \Impensavel\Essence\EssenceException
     *
     * @param   MockBuilder $mockBuilder
     * @return  void
     */
    public function testExtractFailInvalidURL(MockBuilder $mockBuilder)
    {
        // Do not replace methods
        $essence = $mockBuilder->setMethods(null)->getMock();

        $essence->extract(array(
            'function' => 'baz',
        ));
    }

    /**
     * Test extract() method to PASS
     *
     * @depends testInstantiationPass
     * @depends testInputFilesPass
     *
     * @param   MockBuilder $mockBuilder
     * @param   array       $files
     * @return  void
     */
    public function testExtractPass(MockBuilder $mockBuilder, array $files)
    {
        $response = file_get_contents($files['response']);

        $essence = $mockBuilder->setMethods(array(
            'makeCall',
        ))
        ->getMock();

        $essence->method('makeCall')->willReturn($response);

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
     * @depends testInstantiationPass
     * @depends testInputFilesPass
     *
     * @param   MockBuilder $mockBuilder
     * @param   array       $files
     * @return  void
     */
    public function testDumpPass(MockBuilder $mockBuilder, array $files)
    {
        $response = file_get_contents($files['response']);

        $essence = $mockBuilder->setMethods(array(
            'makeCall',
        ))
        ->getMock();

        $essence->method('makeCall')->willReturn($response);

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

    /**
     * Test dump() method to FAIL (invalid input)
     *
     * @depends                  testInstantiationPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage The input must be an associative array
     *
     * @param   MockBuilder $mockBuilder
     * @return  void
     */
    public function testDumpFailInvalidInput(MockBuilder $mockBuilder)
    {
        // Do not replace methods
        $essence = $mockBuilder->setMethods(null)->getMock();

        $essence->dump('something');
    }
}

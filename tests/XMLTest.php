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

use SplFileInfo;

use PHPUnit_Framework_TestCase;

use Impensavel\Essence\XML;

class XMLTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test input file to PASS (readability)
     *
     * @access  public
     * @return  array
     */
    public function testInputFilesPass()
    {
        $files = array(
            'invalid' => __DIR__.'/input/xml/invalid.xml',
            'valid'   => __DIR__.'/input/xml/valid.xml',
        );

        foreach ($files as $file) {
            $this->assertTrue(is_readable($file));
        }

        return $files;
    }

    /**
     * Test instantiation to FAIL (property map must be an array)
     *
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage [Persons/Person] Element property map must be an array
     *
     * @access  public
     * @return  void
     */
    public function testInstantiationFailMapMustBeArray()
    {
        new XML(array(
            '/Persons/Person' => array(
                'map' => true,
            ),
        ));
    }

    /**
     * Test instantiation to FAIL (Data handler not set)
     *
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage [Persons/Person] Element data handler is not set
     *
     * @access  public
     * @return  void
     */
    public function testInstantiationFailDataHandlerNotSet()
    {
        new XML(array(
            '/Persons/Person' => array(
                'map' => array(
                    'name'    => 'string(Name)',
                    'surname' => 'string(Surname)',
                    'email'   => 'string(Email)',
                ),
            ),
        ));
    }

    /**
     * Test instantiation to FAIL (invalid Data handler)
     *
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage [Persons/Person] Element data handler must be a Closure
     *
     * @access  public
     * @return  void
     */
    public function testInstantiationFailInvalidDataHandler()
    {
        new XML(array(
            '/Persons/Person' => array(
                'map'     => array(
                    'name'    => 'string(Name)',
                    'surname' => 'string(Surname)',
                    'email'   => 'string(Email)',
                ),
                'handler' => true,
            ),
        ));
    }

    /**
     * Test instantiation to PASS
     *
     * @access  public
     * @return  XML
     */
    public function testInstantiationPass()
    {
        $essence = new XML(array(
            '/Persons/Person' => array(
                'map'     => array(
                    'name'    => 'string(Name)',
                    'surname' => 'string(Surname)',
                    'email'   => 'string(Email)',
                ),
                'handler' => function () {
                    // simulate a last inserted id
                    return rand(1, 100);
                },
            ),
            '/Persons/Person/Addresses/Address' => array(
                'map'     => array(
                    'person_id' => '#/Persons/Person',
                    'type'      => 'string(@Type)',
                    'address'   => 'string(Name)',
                    'postcode'  => 'string(Postcode)',
                ),
                'handler' => function () {
                    // ...
                },
            ),
        ));

        $this->assertInstanceOf('\Impensavel\Essence\XML', $essence);

        return $essence;
    }

    /**
     * Test string extract() method to FAIL (invalid input type)
     *
     * @depends                  testInstantiationPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage Invalid input type: boolean
     *
     * @access  public
     * @param   XML    $essence
     * @return  void
     */
    public function testExtractFailInvalidInputType(XML $essence)
    {
        $essence->extract(true);
    }

    /**
     * Test SplFileInfo extract() method to FAIL (invalid file)
     *
     * @depends                  testInstantiationPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage Could not open "invalid.xml" for parsing
     *
     * @access  public
     * @param   XML    $essence
     * @return  void
     */
    public function testExtractSplFileInfoFailInvalidFile(XML $essence)
    {
        $input = new SplFileInfo('invalid.xml');

        $essence->extract($input);
    }

    /**
     * Test string extract() method to PASS
     *
     * @depends testInstantiationPass
     * @depends testInputFilesPass
     *
     * @access  public
     * @param   XML    $essence
     * @param   array  $files
     * @return  void
     */
    public function testExtractStringPass(XML $essence, array $files)
    {
        $input = file_get_contents($files['valid']);

        $result = $essence->extract($input);

        $this->assertTrue($result);
    }

    /**
     * Test SplFileInfo extract() method to PASS
     *
     * @depends testInstantiationPass
     * @depends testInputFilesPass
     *
     * @access  public
     * @param   XML    $essence
     * @param   array  $files
     * @return  void
     */
    public function testExtractSplFileInfoPass(XML $essence, array $files)
    {
        $input = new SplFileInfo($files['valid']);

        $result = $essence->extract($input);

        $this->assertTrue($result);
    }

    /**
     * Test Resource extract() method to PASS
     *
     * @depends testInstantiationPass
     * @depends testInputFilesPass
     *
     * @access  public
     * @param   XML    $essence
     * @param   array  $files
     * @return  void
     */
    public function testExtractResourcePass(XML $essence, array $files)
    {
        $input = fopen($files['valid'], 'r');

        $result = $essence->extract($input);

        $this->assertTrue($result);
    }

    /**
     * Test Resource extract() method to FAIL (invalid resource type)
     *
     * @depends                  testInstantiationPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage Invalid resource type: curl
     *
     * @access  public
     * @param   XML    $essence
     * @return  void
     */
    public function testExtractResourceFailInvalidType(XML $essence)
    {
        // create a resource of a type different than stream
        $input = curl_init();

        $essence->extract($input);
    }

    /**
     * Test extract() method to FAIL (invalid XML)
     *
     * @depends                  testInstantiationPass
     * @depends                  testInputFilesPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage xmlParseEntityRef: no name @ line #9 [Persons/Person]
     *
     * @access  public
     * @param   XML    $essence
     * @param   array  $files
     * @return  void
     */
    public function testExtractFailInvalidXML(XML $essence, array $files)
    {
        $input = new SplFileInfo($files['invalid']);

        $essence->extract($input);
    }
}
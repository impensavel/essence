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

use SplFileInfo;

use PHPUnit_Framework_TestCase;

class XMLEssenceTest extends PHPUnit_Framework_TestCase
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
            __DIR__.'/input/xml/valid.xml',
            __DIR__.'/input/xml/invalid.xml',
        );

        foreach ($files as $file) {
            $this->assertTrue(is_readable($file));
        }

        return $files;
    }

    /**
     * Test instantiation to FAIL (map must be an array)
     *
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage [Persons/Person] Element map must be an array
     *
     * @access  public
     * @return  void
     */
    public function testInstantiationFailMapMustBeArray()
    {
        new XMLEssence(array(
            '/Persons/Person' => array(
                'map' => true,
            ),
        ));
    }

    /**
     * Test instantiation to FAIL (callback not set)
     *
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage [Persons/Person] Element callback must be set
     *
     * @access  public
     * @return  void
     */
    public function testInstantiationFailCallbackNotSet()
    {
        new XMLEssence(array(
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
     * Test instantiation to FAIL (invalid callback)
     *
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage [Persons/Person] Element callback must a Closure
     *
     * @access  public
     * @return  void
     */
    public function testInstantiationFailInvalidCallback()
    {
        new XMLEssence(array(
            '/Persons/Person' => array(
                'map'      => array(
                    'name'    => 'string(Name)',
                    'surname' => 'string(Surname)',
                    'email'   => 'string(Email)',
                ),
                'callback' => true,
            ),
        ));
    }

    /**
     * Test instantiation to PASS
     *
     * @access  public
     * @return  XMLEssence
     */
    public function testInstantiationPass()
    {
        $essence = new XMLEssence(array(
            '/Persons/Person' => array(
                'map'      => array(
                    'name'    => 'string(Name)',
                    'surname' => 'string(Surname)',
                    'email'   => 'string(Email)',
                ),
                'callback' => function () {
                    // simulate a last inserted id
                    return rand(1, 100);
                },
            ),
            '/Persons/Person/Addresses/Address' => array(
                'map'      => array(
                    'person_id' => '#/Persons/Person',
                    'type'      => 'string(@Type)',
                    'address'   => 'string(Name)',
                    'postcode'  => 'string(Postcode)',
                ),
                'callback' => function () {},
            ),
        ));

        $this->assertInstanceOf('\Impensavel\Essence\XMLEssence', $essence);

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
     * @param   XMLEssence $essence
     * @return  void
     */
    public function testExtractFailInvalidInputType(XMLEssence $essence)
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
     * @param   XMLEssence $essence
     * @return  void
     */
    public function testExtractSplFileInfoFailInvalidFile(XMLEssence $essence)
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
     * @param   XMLEssence $essence
     * @param   array      $files
     * @return  void
     */
    public function testExtractStringPass(XMLEssence $essence, array $files)
    {
        $input = file_get_contents(current($files));

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
     * @param   XMLEssence $essence
     * @param   array      $files
     * @return  void
     */
    public function testExtractSplFileInfoPass(XMLEssence $essence, array $files)
    {
        $input = new SplFileInfo(current($files));

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
     * @param   XMLEssence $essence
     * @param   array      $files
     * @return  void
     */
    public function testExtractResourcePass(XMLEssence $essence, array $files)
    {
        $input = fopen(current($files), 'r');

        $result = $essence->extract($input);

        $this->assertTrue($result);
    }

    /**
     * Test Resource extract() method to FAIL (invalid resource type)
     *
     * @depends                  testInstantiationPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage Invalid resource type: Socket
     *
     * @access  public
     * @param   XMLEssence $essence
     * @return  void
     */
    public function testExtractResourceFailInvalidType(XMLEssence $essence)
    {
        $input = socket_create(AF_UNIX, SOCK_STREAM, 0);

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
     * @param   XMLEssence $essence
     * @param   array      $files
     * @return  void
     */
    public function testExtractFailInvalidXML(XMLEssence $essence, array $files)
    {
        $input = new SplFileInfo(end($files));

        $essence->extract($input);
    }
}

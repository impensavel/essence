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
     * @return  string
     */
    public function testInputFilePass()
    {
        $file = __DIR__.'/input/xml/person.xml';

        $this->assertTrue(is_readable($file));

        return $file;
    }

    /**
     * Test instantiation to FAIL (map empty/not set)
     *
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage [Persons/Person] Element map empty/not set
     *
     * @access  public
     * @return  void
     */
    public function testInstantiationFailMapEmptyNotSet()
    {
        new XMLEssence(array(
            '/Persons/Person' => array(),
        ));
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
     * @depends testInputFilePass
     *
     * @access  public
     * @param   XMLEssence $essence
     * @param   string     $file
     * @return  void
     */
    public function testExtractStringPass(XMLEssence $essence, $file)
    {
        $input = file_get_contents($file);

        $result = $essence->extract($input);

        $this->assertTrue($result);
    }

    /**
     * Test SplFileInfo extract() method to PASS
     *
     * @depends testInstantiationPass
     * @depends testInputFilePass
     *
     * @access  public
     * @param   XMLEssence $essence
     * @param   string     $file
     * @return  void
     */
    public function testExtractSplFileInfoPass(XMLEssence $essence, $file)
    {
        $input = new SplFileInfo($file);

        $result = $essence->extract($input);

        $this->assertTrue($result);
    }

    /**
     * Test Resource extract() method to PASS
     *
     * @depends testInstantiationPass
     * @depends testInputFilePass
     *
     * @access  public
     * @param   XMLEssence $essence
     * @param   string     $file
     * @return  void
     */
    public function testExtractResourcePass(XMLEssence $essence, $file)
    {
        $input = fopen($file, 'r');

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
}

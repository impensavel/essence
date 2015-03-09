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

class CSVEssenceTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test input files to PASS (readability)
     *
     * @access  public
     * @return  array
     */
    public function testInputFilesPass()
    {
        $files = array(
            __DIR__.'/input/csv/macintosh.csv',
            __DIR__.'/input/csv/unix.csv',
            __DIR__.'/input/csv/windows.csv',
        );

        foreach ($files as $file) {
            $this->assertTrue(is_readable($file));
        }

        return $files;
    }

    /**
     * Test instantiation to FAIL (map empty/not set)
     *
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage [default] Element map empty/not set
     *
     * @access  public
     * @return  void
     */
    public function testInstantiationFailMapEmptyNotSet()
    {
        new CSVEssence(array());
    }

    /**
     * Test instantiation to FAIL (map must be an array)
     *
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage [default] Element map must be an array
     *
     * @access  public
     * @return  void
     */
    public function testInstantiationFailMapMustBeArray()
    {
        new CSVEssence(array(
            'map' => true,
        ));
    }

    /**
     * Test instantiation to FAIL (callback not set)
     *
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage [default] Element callback must be set
     *
     * @access  public
     * @return  void
     */
    public function testInstantiationFailCallbackNotSet()
    {
        new CSVEssence(array(
            'map' => array(
                'name'    => 0,
                'surname' => 1,
            ),
        ));
    }

    /**
     * Test instantiation to FAIL (invalid callback)
     *
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage [default] Element callback must a Closure
     *
     * @access  public
     * @return  void
     */
    public function testInstantiationFailInvalidCallback()
    {
        new CSVEssence(array(
            'map'      => array(
                'name'    => 0,
                'surname' => 1,
            ),
            'callback' => true
        ));
    }

    /**
     * Test instantiation to PASS
     *
     * @access  public
     * @return  CSVEssence
     */
    public function testInstantiationPass()
    {
        $essence = new CSVEssence(array(
            'map'      => array(
                'name'    => 0,
                'surname' => 1,
            ),
            'callback' => function ($data) {
                $this->assertArrayHasKey('properties', $data);
                $this->assertArrayHasKey('extra', $data);
                $this->assertArrayHasKey('line', $data);
            },
        ));

        $this->assertInstanceOf('\Impensavel\Essence\CSVEssence', $essence);

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
     * @param   CSVEssence $essence
     * @return  void
     */
    public function testExtractFailInvalidInputType(CSVEssence $essence)
    {
        $essence->extract(true);
    }

    /**
     * Test string extract() method to FAIL (invalid column)
     *
     * @depends                  testInstantiationPass
     * @depends                  testInputFilesPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage Invalid column 1 @ line 3 for property "surname"
     *
     * @access  public
     * @param   CSVEssence $essence
     * @param   array      $files
     * @return  void
     */
    public function testExtractStringFailInvalidColumn(CSVEssence $essence, array $files)
    {
        $input = file_get_contents(current($files));

        $essence->extract($input);
    }

    /**
     * Test string extract() method to PASS (suppress exceptions)
     *
     * @depends testInstantiationPass
     * @depends testInputFilesPass
     *
     * @access  public
     * @param   CSVEssence $essence
     * @param   array      $files
     * @return  void
     */
    public function testExtractStringPassNoExceptions(CSVEssence $essence, array $files)
    {
        foreach ($files as $file) {
            $input = file_get_contents($file);

            $result = $essence->extract($input, array(
                'exceptions' => false,
            ));

            $this->assertTrue($result);
        }
    }

    /**
     * Test SplFileInfo extract() method to FAIL (invalid file)
     *
     * @depends                  testInstantiationPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage Could not open "invalid.csv" for parsing
     *
     * @access  public
     * @param   CSVEssence $essence
     * @return  void
     */
    public function testExtractSplFileInfoFailInvalidFile(CSVEssence $essence)
    {
        $input = new SplFileInfo('invalid.csv');

        $essence->extract($input);
    }

    /**
     * Test SplFileInfo extract() method to FAIL (invalid column)
     *
     * @depends                  testInstantiationPass
     * @depends                  testInputFilesPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage Invalid column 1 @ line 3 for property "surname"
     *
     * @access  public
     * @param   CSVEssence $essence
     * @param   array      $files
     * @return  void
     */
    public function testExtractSplFileInfoFailInvalidColumn(CSVEssence $essence, array $files)
    {
        $input = new SplFileInfo(current($files));

        $essence->extract($input, array(
            'auto_eol' => true, // detect EOL from macintosh.csv
        ));
    }

    /**
     * Test SplFileInfo extract() method to PASS (suppress exceptions)
     *
     * @depends testInstantiationPass
     * @depends testInputFilesPass
     *
     * @access  public
     * @param   CSVEssence $essence
     * @param   array      $files
     * @return  void
     */
    public function testExtractSplFileInfoPassNoExceptions(CSVEssence $essence, array $files)
    {
        foreach ($files as $file) {
            $input = new SplFileInfo($file);

            $result = $essence->extract($input, array(
                'exceptions' => false,
            ));

            $this->assertTrue($result);
        }
    }

    /**
     * Test resource extract() method to FAIL (invalid column)
     *
     * @depends                  testInstantiationPass
     * @depends                  testInputFilesPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage Invalid column 1 @ line 3 for property "surname"
     *
     * @access  public
     * @param   CSVEssence $essence
     * @param   array      $files
     * @return  void
     */
    public function testExtractResourceFailInvalidColumn(CSVEssence $essence, array $files)
    {
        $input = fopen(current($files), 'r');

        $essence->extract($input);
    }

    /**
     * Test Resource extract() method to PASS (suppress exceptions)
     *
     * @depends testInstantiationPass
     * @depends testInputFilesPass
     *
     * @access  public
     * @param   CSVEssence $essence
     * @param   array      $files
     * @return  void
     */
    public function testExtractResourcePassNoExceptions(CSVEssence $essence, array $files)
    {
        foreach ($files as $file) {
            $input = fopen($file, 'r');

            $result = $essence->extract($input, array(
                'exceptions' => false,
            ));

            $this->assertTrue($result);

            $this->assertTrue(fclose($input));
        }
    }

    /**
     * Test Resource extract() method to FAIL (invalid resource type)
     *
     * @depends                  testInstantiationPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage Invalid resource type: Socket
     *
     * @access  public
     * @param   CSVEssence $essence
     * @return  void
     */
    public function testExtractResourceFailInvalidType(CSVEssence $essence)
    {
        $input = socket_create(AF_UNIX, SOCK_STREAM, 0);

        $essence->extract($input);
    }
}

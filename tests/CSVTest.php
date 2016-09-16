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

use Impensavel\Essence\CSV;

class CSVTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test input files to PASS (readability)
     *
     * @return  array
     */
    public function testInputFilesPass()
    {
        $files = array(
            'mac' => __DIR__.'/input/csv/macintosh.csv',
            'nix' => __DIR__.'/input/csv/unix.csv',
            'win' => __DIR__.'/input/csv/windows.csv',
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
     * @expectedExceptionMessage [default] Element property map must be an array
     *
     * @return  void
     */
    public function testInstantiationFailMapMustBeArray()
    {
        new CSV(array(
            'map' => true,
        ));
    }

    /**
     * Test instantiation to FAIL (Data handler not set)
     *
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage [default] Element data handler is not set
     *
     * @return  void
     */
    public function testInstantiationFailDataHandlerNotSet()
    {
        new CSV(array(
            'map' => array(
                'name'    => 0,
                'surname' => 1,
            ),
        ));
    }

    /**
     * Test instantiation to FAIL (invalid Data handler)
     *
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage [default] Element data handler must be a Closure
     *
     * @return  void
     */
    public function testInstantiationFailInvalidDataHandler()
    {
        new CSV(array(
            'map'     => array(
                'name'    => 0,
                'surname' => 1,
            ),
            'handler' => true
        ));
    }

    /**
     * Test instantiation to PASS
     *
     * @return  CSV
     */
    public function testInstantiationPass()
    {
        $essence = new CSV(array(
            'map'     => array(
                'name'    => 0,
                'surname' => 1,
            ),
            'handler' => function () {
                // ...
            },
        ));

        $this->assertInstanceOf('\Impensavel\Essence\CSV', $essence);

        return $essence;
    }

    /**
     * Test string extract() method to FAIL (invalid input type)
     *
     * @depends                  testInstantiationPass
     * @expectedException        \Impensavel\Essence\EssenceException
     * @expectedExceptionMessage Invalid input type: boolean
     *
     * @param   CSV $essence
     * @return  void
     */
    public function testExtractFailInvalidInputType(CSV $essence)
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
     * @param   CSV   $essence
     * @param   array $files
     * @return  void
     */
    public function testExtractStringFailInvalidColumn(CSV $essence, array $files)
    {
        $input = file_get_contents($files['mac']);

        $essence->extract($input);
    }

    /**
     * Test string extract() method to PASS (suppress exceptions)
     *
     * @depends testInstantiationPass
     * @depends testInputFilesPass
     *
     * @param   CSV   $essence
     * @param   array $files
     * @return  void
     */
    public function testExtractStringPassNoExceptions(CSV $essence, array $files)
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
     * @param   CSV $essence
     * @return  void
     */
    public function testExtractSplFileInfoFailInvalidFile(CSV $essence)
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
     * @param   CSV   $essence
     * @param   array $files
     * @return  void
     */
    public function testExtractSplFileInfoFailInvalidColumn(CSV $essence, array $files)
    {
        $input = new SplFileInfo($files['mac']);

        $essence->extract($input, array(
            'auto_eol' => true, // Detect EOL from macintosh.csv
        ));
    }

    /**
     * Test SplFileInfo extract() method to PASS (suppress exceptions)
     *
     * @depends testInstantiationPass
     * @depends testInputFilesPass
     *
     * @param   CSV   $essence
     * @param   array $files
     * @return  void
     */
    public function testExtractSplFileInfoPassNoExceptions(CSV $essence, array $files)
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
     * @param   CSV   $essence
     * @param   array $files
     * @return  void
     */
    public function testExtractResourceFailInvalidColumn(CSV $essence, array $files)
    {
        $input = fopen($files['mac'], 'r');

        $essence->extract($input);
    }

    /**
     * Test Resource extract() method to PASS (suppress exceptions)
     *
     * @depends testInstantiationPass
     * @depends testInputFilesPass
     *
     * @param   CSV   $essence
     * @param   array $files
     * @return  void
     */
    public function testExtractResourcePassNoExceptions(CSV $essence, array $files)
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
     * @expectedExceptionMessage Invalid resource type: curl
     *
     * @param   CSV $essence
     * @return  void
     */
    public function testExtractResourceFailInvalidType(CSV $essence)
    {
        // Create a resource of a type other than stream
        $input = curl_init();

        $essence->extract($input);
    }
}

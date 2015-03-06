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

class XMLEssenceTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test instantiation to PASS
     *
     * @access  public
     * @return  XMLEssence
     */
    public function testInstantiationPass()
    {
        $essence = new XMLEssence(array());

        $this->assertInstanceOf('\Impensavel\Essence\XMLEssence', $essence);

        return $essence;
    }
}

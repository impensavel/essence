<?php
/**
 * This file is part of the Essence library.
 *
 * @author     Quetzy Garcia <quetzyg@impensavel.com>
 * @copyright  2014-2015
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed
 * with this source code.
 */

namespace Impensavel\Essence;

use PHPUnit_Framework_TestCase;

class CSVEssenceTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test instantiation to FAIL (map empty/not set)
	 *
	 * @expectedException        \Impensavel\Essence\EssenceException
	 * @expectedExceptionMessage [default] Element map empty/not set
	 *
	 * @access  public
	 * @return  CSVEssence
	 */
	public function testInstanceFailMapEmptyNotSet()
	{
		$essence = new CSVEssence([]);

		$this->assertInstanceOf('\Impensavel\Essence\CSVEssence', $essence);

		return $essence;
	}
}

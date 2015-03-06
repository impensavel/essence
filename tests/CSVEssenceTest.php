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

class CSVEssenceTest extends PHPUnit_Framework_TestCase
{
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
			'map' => 1,
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
				'foo' => 1,
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
				'foo' => 1,
			),
			'callback' => 1
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
			'map' => array(
				'foo' => 1,
			),
			'callback' => function () {},
		));

		$this->assertInstanceOf('\Impensavel\Essence\CSVEssence', $essence);

		return $essence;
	}
}

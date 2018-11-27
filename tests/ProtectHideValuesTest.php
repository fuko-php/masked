<?php

namespace Fuko\Masked\Tests;

use Fuko\Masked\Protect;
use PHPUnit\Framework\TestCase;

class ProtectHideValuesTest extends TestCase
{
	function tearDown()
	{
		Protect::clearValues();
	}

	/**
	* @covers Fuko\Masked\Protect::hideValue
	*/
	function test_hideValue()
	{
		$this->assertTrue(Protect::hideValue('password'));
		$this->assertNull(Protect::hideValue('password'));

		$this->assertTrue(Protect::hideValue($this));
	}

	function __toString()
	{
		return self::class;
	}

	/**
	* @dataProvider provider_hideValue_bad
	* @covers Fuko\Masked\Protect::hideValue
	* @covers Fuko\Masked\Protect::_validateValue
	* @expectedException PHPUnit_Framework_Error_Warning
	*/
	function test_hideValue_bad($value)
	{
		Protect::hideValue($value);
	}

	function provider_hideValue_bad()
	{
		return array(
			array(''),
			array(0),
			array(null),
			array(array()),
			array($_SERVER),
			array((object) $server),
			array(fopen(__FILE__, 'r')),
			array(opendir(__DIR__)),
		);
	}

	/**
	* @dataProvider provider_hideValues_bad
	* @covers Fuko\Masked\Protect::hideValues
	* @covers Fuko\Masked\Protect::_validateValue
	* @expectedException PHPUnit_Framework_Error_Warning
	*/
	function test_hideValues_bad($values)
	{
		Protect::hideValues( $values );
	}

	function provider_hideValues_bad()
	{
		return array(
			array( array('password', '') ),
			array( array(1,2,3,4,0) ),
			array( array(null) ),
			array( array(array()) ),
			array( array($_SERVER) ),
			array( array( (object) $server) ),
			array( array( fopen(__FILE__, 'r')) ),
			array( array( opendir(__DIR__)) ),
		);
	}
}

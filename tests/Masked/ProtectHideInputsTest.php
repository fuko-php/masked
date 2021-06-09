<?php

namespace Fuko\Masked\Tests;

use Fuko\Masked\Protect;
use PHPUnit\Framework\TestCase;

use const INPUT_COOKIE;
use const INPUT_POST;
use const INPUT_REQUEST;
use const INPUT_SESSION;

class ProtectHideInputsTest extends TestCase
{
	function tearDown(): void
	{
		Protect::clearInputs();
	}

	/**
	* @dataProvider provider_hideInput
	* @covers Fuko\Masked\Protect::hideInput
	* @covers Fuko\Masked\Protect::_validateInput
	*/
	function test_hideInput($name, $type)
	{
		$this->assertTrue(
			Protect::hideInput($name, $type)
		);
	}

	function provider_hideInput()
	{
		return array(
			array('clandestine', INPUT_POST),
			array('incognito', 333),
			array('OLDPWD', INPUT_ENV),
			array('USER', INPUT_SERVER),

			array('cipher', ''),
		);
	}

	/**
	* @dataProvider provider_hideInput_bad
	* @covers Fuko\Masked\Protect::hideInput
	* @covers Fuko\Masked\Protect::_validateInput
	* @expectedException \PHPUnit\Framework\Error\Warning
	*/
	function test_hideInput_bad($name, $type)
	{
		Protect::hideInput($name, $type);
	}

	function provider_hideInput_bad()
	{
		return array(

			array('', INPUT_SESSION),
			array(array(), INPUT_REQUEST),
			array(null, INPUT_COOKIE),
			array($this, INPUT_POST),

			array('cipher', null),
			array('cipher', array()),
			array('cipher', $this),
		);
	}

	/**
	* @dataProvider provider_hideInputs_bad
	* @covers Fuko\Masked\Protect::hideInputs
	* @covers Fuko\Masked\Protect::_validateInput
	* @expectedException \PHPUnit\Framework\Error\Warning
	*/
	function test_hideInputs_bad($inputs)
	{
		Protect::hideInputs( $inputs );
	}

	function provider_hideInputs_bad()
	{
		return array(
			array( array( 44 => null ) ),
			array( array( null ) ),
			array( array( '' ) ),

			array( array( 33 => (object) $_SERVER ) ),
			array( array( 22 => fopen(__FILE__, 'r') ) ),
			array( array( 22 => opendir(__DIR__) ) ),

			array( array( 33 => array( (object) $_SERVER ) ) ),
			array( array( 22 => array( fopen(__FILE__, 'r') ) ) ),
			array( array( 22 => array( opendir(__DIR__) ) ) ),
		);
	}
}

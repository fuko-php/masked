<?php

namespace Fuko\Masked\Tests;

use Fuko\Masked\Protect;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Error\Warning;

use const INPUT_COOKIE;
use const INPUT_POST;
use const INPUT_REQUEST;
use const INPUT_SESSION;

use function fopen;
use function opendir;

class ProtectHideInputsTest extends TestCase
{
	function tearDown(): void
	{
		Protect::clearInputs();
	}

	/**
	* @dataProvider provider_hideInput
	* @covers Fuko\Masked\Protect::hideInput
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
	* @covers Fuko\Masked\Protect::hideInput
	*/
	function test_hideInput_withEmptyName()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideInput() $name argument is empty'
			);
		Protect::hideInput('', INPUT_SESSION);
	}

	/**
	* @covers Fuko\Masked\Protect::hideInput
	*/
	function test_hideInput_withEmptyArray()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideInput() $name argument is empty'
			);
		Protect::hideInput(array(), INPUT_REQUEST);
	}

	/**
	* @covers Fuko\Masked\Protect::hideInput
	*/
	function test_hideInput_withNull()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideInput() $name argument is empty'
			);
		Protect::hideInput(null, INPUT_COOKIE);
	}

	/**
	* @covers Fuko\Masked\Protect::hideInput
	*/
	function test_hideInput_withObject()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideInput() $name argument is not scalar, it is object'
			);
		Protect::hideInput($this, INPUT_POST);
	}

	/**
	* @covers Fuko\Masked\Protect::hideInput
	*/
	function test_hideInput_withNullType()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideInput() $type argument is not scalar, it is NULL'
			);
		Protect::hideInput('cipher', null);
	}

	/**
	* @covers Fuko\Masked\Protect::hideInput
	*/
	function test_hideInput_withArrayType()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideInput() $type argument is not scalar, it is array'
			);
		Protect::hideInput('cipher', array());
	}

	/**
	* @covers Fuko\Masked\Protect::hideInput
	*/
	function test_hideInput_withObectType()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideInput() $type argument is not scalar, it is object'
			);
		Protect::hideInput('cipher', $this);
	}

	/**
	* @covers Fuko\Masked\Protect::hideInputs
	*/
	function test_hideInputs_withNullName()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideInputs() empty input names for "44" input type'
			);
		Protect::hideInputs( array( 44 => null ) );
	}

	/**
	* @covers Fuko\Masked\Protect::hideInputs
	*/
	function test_hideInputs_withNullInput()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideInputs() empty input names for "0" input type'
			);
		Protect::hideInputs( array( null ) );
	}

	/**
	* @covers Fuko\Masked\Protect::hideInputs
	*/
	function test_hideInputs_withEmptyString()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideInputs() empty input names for "0" input type'
			);
		Protect::hideInputs( array( '' ) );
	}

	/**
	* @covers Fuko\Masked\Protect::hideInputs
	*/
	function test_hideInputs_withObjectInput()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideInputs() input names must be string or array, object provided instead'
			);
		Protect::hideInputs( array( 33 => (object) $_SERVER ) );
	}

	/**
	* @covers Fuko\Masked\Protect::hideInputs
	*/
	function test_hideInputs_withOtherInput()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideInputs() input names must be string or array, resource provided instead'
			);
		Protect::hideInputs( array(22 => fopen(__FILE__, 'r') ) );
	}

	/**
	* @covers Fuko\Masked\Protect::hideInputs
	*/
	function test_hideInputs_withObjectInputName()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideInputs() $name argument is not scalar, it is object'
			);
		Protect::hideInputs( array( 33 => array( (object) $_SERVER ) ) );
	}

	/**
	* @covers Fuko\Masked\Protect::hideInputs
	*/
	function test_hideInputs_withOtherInputName()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideInputs() $name argument is not scalar, it is resource'
			);
		Protect::hideInputs( array( 22 => array( opendir(__DIR__) ) ) );
	}
}

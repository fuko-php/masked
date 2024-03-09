<?php

namespace Fuko\Masked\Tests;

use Fuko\Masked\Protect;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Error\Warning;

class ProtectHideValuesTest extends TestCase
{
	function tearDown(): void
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

	function __toString(): string
	{
		return self::class;
	}

	/**
	* @dataProvider provider_hideValue_empty
	* @covers Fuko\Masked\Protect::hideValue
	*/
	function test_hideValue_empty($value)
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideValue() received an empty value as a hide value'
			);

		Protect::hideValue($value);
	}

	function provider_hideValue_empty()
	{
		return array(
			array(''),
			array(0),
			array(null),
			array(array()),
		);
	}

	/**
	* @covers Fuko\Masked\Protect::hideValue
	*/
	function test_hideValue_array()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideValue() received an array as a hide value'
			);

		Protect::hideValue($_SERVER);
	}

	/**
	* @covers Fuko\Masked\Protect::hideValue
	*/
	function test_hideValue_object()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideValue() received an object as a hide value'
			);

		Protect::hideValue((object) $_SERVER);
	}

	/**
	* @covers Fuko\Masked\Protect::hideValue
	*/
	function test_hideValue_other()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessageMatches(
			'~^Fuko\\\\Masked\\\\Protect\:\:hideValue\(\) received unexpected type \(Resource id #\d+\) as a hide value$~'
			);

		Protect::hideValue(opendir(__DIR__));
	}

	/**
	* @covers Fuko\Masked\Protect::hideValues
	*/
	function test_hideValues_emptyString()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideValues() received an empty value as a hide value (key "1" of the $values argument)'
			);

		Protect::hideValues( array('password', '') );
	}

	/**
	* @covers Fuko\Masked\Protect::hideValues
	*/
	function test_hideValues_emptyArray()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideValues() received an empty value as a hide value (key "0" of the $values argument)'
			);

		Protect::hideValues( array(array()) );
	}

	/**
	* @covers Fuko\Masked\Protect::hideValues
	*/
	function test_hideValues_Zero()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideValues() received an empty value as a hide value (key "4" of the $values argument)'
			);

		Protect::hideValues( array(1,2,3,4,0) );
	}

	/**
	* @covers Fuko\Masked\Protect::hideValues
	*/
	function test_hideValues_Null()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideValues() received an empty value as a hide value (key "0" of the $values argument)'
			);

		Protect::hideValues( array(null, 'null') );
	}

	/**
	* @covers Fuko\Masked\Protect::hideValues
	*/
	function test_hideValues_withArray()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideValues() received an array as a hide value (key "0" of the $values argument)'
			);

		Protect::hideValues( array($_SERVER) );
	}

	/**
	* @covers Fuko\Masked\Protect::hideValues
	*/
	function test_hideValues_withObject()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideValues() received an object as a hide value (key "0" of the $values argument)'
			);

		Protect::hideValues( array( (object) $_SERVER) );
	}

	/**
	* @covers Fuko\Masked\Protect::hideValues
	*/
	function test_hideValues_withOther()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessageMatches(
			'~^Fuko\\\\Masked\\\\Protect\:\:hideValues\(\) received unexpected type \(Resource id #\d+\) as a hide value \(key "0" of the \$values argument\)$~'
			);

		Protect::hideValues(array(opendir(__DIR__)));
	}
}

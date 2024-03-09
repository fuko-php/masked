<?php

namespace Fuko\Masked\Tests;

use Fuko\Masked\ValueCollection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Error\Warning;

class ValueCollectionTest extends TestCase
{
	/**
	* @covers Fuko\Masked\ValueCollection::hideValue()
	*/
	function testHideEmptyValue()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage('Fuko\Masked\Protect::hideValue() received an empty value as a hide value');

		$c = new ValueCollection;
		$c->hideValue([]);
	}

	/**
	* @covers Fuko\Masked\ValueCollection::hideValue()
	*/
	function testHideEmptyString()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage('Fuko\Masked\Protect::hideValue() received an empty value as a hide value');

		$c = new ValueCollection;
		$c->hideValue('');
	}

	/**
	* @covers Fuko\Masked\ValueCollection::hideValue()
	*/
	function testHideArray()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage('Fuko\Masked\Protect::hideValue() received an array as a hide value');

		$c = new ValueCollection;
		$c->hideValue([123, 234]);
	}

	/**
	* @covers Fuko\Masked\ValueCollection::hideValue()
	*/
	function testHideStdClass()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage('Fuko\Masked\Protect::hideValue() received an object as a hide value');

		$c = new ValueCollection;
		$c->hideValue( (object) ['a' => 123] );
	}

	/**
	* @covers Fuko\Masked\ValueCollection::hideValue()
	* @covers Fuko\Masked\ValueCollection::getValues()
	*/
	function testHideException()
	{
		$c = new ValueCollection;
		$this->assertTrue( $c->hideValue( $e = new \Exception ) );
		$this->assertEquals( $c->getValues(), [$e->__toString()] );
	}

	/**
	* @covers Fuko\Masked\ValueCollection::hideValue()
	*/
	function testHideFileHandler()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessageMatches(
			'~^Fuko\\\\Masked\\\\Protect\:\:hideValue\(\) received unexpected type \(Resource id #\d+\) as a hide value$~'
			);

		$c = new ValueCollection;
		$c->hideValue( fopen(__FILE__, 'r') );
	}

	/**
	* @covers Fuko\Masked\ValueCollection::hideValue()
	* @covers Fuko\Masked\ValueCollection::getValues()
	*/
	function testHideDuplicateValue()
	{
		$c = new ValueCollection;
		$this->assertTrue( $c->hideValue( 'secret' ) );
		$this->assertNull( $c->hideValue( 'secret' ) );
		$this->assertEquals( $c->getValues(), ['secret'] );
	}

	/**
	* @covers Fuko\Masked\ValueCollection::hideValues()
	*/
	function testHideEmptyValues()
	{
		$this->expectException(Warning::class);
		$this->expectExceptionMessage(
			'Fuko\Masked\Protect::hideValues() received an empty value as a hide value (key "0" of the $values argument)'
			);

		$c = new ValueCollection;
		$c->hideValues( [''] );
	}

	/**
	* @covers Fuko\Masked\ValueCollection::hideValues()
	* @covers Fuko\Masked\ValueCollection::getValues()
	* @covers Fuko\Masked\ValueCollection::clearValues()
	*/
	function testHideValues()
	{
		$c = new ValueCollection;

		$c->hideValues( $hide = ['secret', 'parola'] );
		$this->assertEquals( $c->getValues(), $hide );

		$c->clearValues();
		$this->assertEquals( $c->getValues(), [] );
	}
}

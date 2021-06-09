<?php

namespace Fuko\Masked\Tests;

use Fuko\Masked\ValueCollection;
use PHPUnit\Framework\TestCase;

class ValueCollectionTest extends TestCase
{
	/**
	* @covers Fuko\Masked\ValueCollection::hideValue()
	* @expectedException \PHPUnit\Framework\Error\Warning
	*/
	function testHideEmptyValue()
	{
		$c = new ValueCollection;
		$c->hideValue([]);
	}

	/**
	* @covers Fuko\Masked\ValueCollection::hideValue()
	* @expectedException \PHPUnit\Framework\Error\Warning
	*/
	function testHideEmptyString()
	{
		$c = new ValueCollection;
		$c->hideValue('');
	}

	/**
	* @covers Fuko\Masked\ValueCollection::hideValue()
	* @expectedException \PHPUnit\Framework\Error\Warning
	*/
	function testHideArray()
	{
		$c = new ValueCollection;
		$c->hideValue([123, 234]);
	}

	/**
	* @covers Fuko\Masked\ValueCollection::hideValue()
	* @expectedException \PHPUnit\Framework\Error\Warning
	*/
	function testHideStdClass()
	{
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
	* @expectedException \PHPUnit\Framework\Error\Warning
	*/
	function testHideFileHandler()
	{
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
	* @expectedException \PHPUnit\Framework\Error\Warning
	*/
	function testHideEmptyValues()
	{
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

<?php

namespace Fuko\Masked\Tests;

use Fuko\Masked\Redact;
use PHPUnit\Framework\TestCase;

class setRedactCallbackTest extends TestCase
{
	function tearDown()
	{
		Redact::setRedactCallback(
			array(Redact::class, 'disguise'),
			array(0, '█')
		);
	}

	protected $mask_flag = false;

	function mask_even($value, $symbol)
	{
		for ($i = 0; $i < strlen($value); $i++)
		{
			if ($i%2)
			{
				$value[ $i ] = $symbol;
			}
		}

		$this->mask_flag = true;

		return $value;
	}

	/**
	* @covers Fuko\Masked\Redact::setRedactCallback
	* @covers Fuko\Masked\Redact::redact
	*/
	function test_mask()
	{
		Redact::setRedactCallback(
			array($this, 'mask_even'),
			array('-')
		);

		$this->mask_flag = false;

		$this->assertEquals(
			Redact::redact('12345'),
			'1-3-5'
		);

		$this->assertTrue($this->mask_flag);
	}

	function dudu($value)
	{
		return '💩';
	}

	/**
	* @covers Fuko\Masked\Redact::setRedactCallback
	* @covers Fuko\Masked\Redact::redact
	*/
	function test_dudu()
	{
		Redact::setRedactCallback(
			array($this, 'dudu'),
			array('-')
		);

		$this->assertEquals(
			Redact::redact('12345'),
			'💩'
		);
	}

	/**
	* @covers Fuko\Masked\Redact::setRedactCallback
	* @expectedException InvalidArgumentException
	*/
	function test_setRedactCallback1()
	{
		Redact::setRedactCallback(
			array($this, '_')
			);
	}

	private function poop() {}

	/**
	* @covers Fuko\Masked\Redact::setRedactCallback
	* @expectedException InvalidArgumentException
	*/
	function test_setRedactCallback2()
	{
		$this->poop();
		Redact::setRedactCallback(
			array($this, 'poop')
			);
	}
}

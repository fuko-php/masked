<?php

namespace Fuko\Masked\Tests;

use Fuko\Masked\Redact;
use PHPUnit\Framework\TestCase;

class RedactDisguiseTest extends TestCase
{
	/**
	* @dataProvider provider_disguise_masking
	* @covers Fuko\Masked\Redact::disguise
	*/
	function test_disguise_masking($source, $masked)
	{
		$this->assertEquals(
			Redact::disguise($source),
			$masked
		);
	}

	function provider_disguise_masking()
	{
		return array(
			array(-123, '****'),
			array(-1234, '***34'),
			array(1234, '****'),
			array(null, ''),
			array('', ''),
			array(0, '*'),
			array(00, '*'),
			array('41111111111111111', '*************1111'),
			array([], ''),
			array((object) $_SERVER, ''),
			array($this, ''),
		);
	}

	/**
	* @dataProvider provider_disguise_unmasked
	* @covers Fuko\Masked\Redact::disguise
	*/
	function test_disguise_unmasked($source, $unmasked, $masked)
	{
		$this->assertEquals(
			Redact::disguise($source, $unmasked),
			$masked
		);
	}

	function provider_disguise_unmasked()
	{
		return array(
			array(1234, 0, '****'),
			array(1234, 1, '***4'),
			array(1234, 4, '****'),
			array(1234, -1, '1***'),
			array(1234, -4, '****'),
			array(1234, 2/3, '****'),
			array(1234, 1.9, '***4'),
			array(1234, [], '****'),
		);
	}

	/**
	* @dataProvider provider_disguise_symbol
	* @covers Fuko\Masked\Redact::disguise
	*/
	function test_disguise_symbol($source, $unmasked, $symbol, $masked)
	{
		$this->assertEquals(
			Redact::disguise($source, $unmasked, $symbol),
			$masked
		);
	}

	function provider_disguise_symbol()
	{
		return array(
			array('1234', 0, '█', '████'),
			array('1234', 0, 'xX', 'xXxXxXxX'),

			array('1234', 0, '', ''),
			array('1234', 1, '', '4'),
			array('1234', -1, '', '1'),
			array('1234', -4, '', ''),
			array('12345', 4, '', '45'),
			array('12345', -4, '', '12'),

			array('1234', 0, null, ''),
			array('crap', 0, '💩', '💩💩💩💩'),
			array('shit', 3, '💩', '💩💩it'),
			array('shit', -2, '💩', 'sh💩💩'),

		);
	}
}

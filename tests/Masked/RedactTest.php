<?php

namespace Fuko\Masked\Tests;

use Fuko\Masked\Redact;
use PHPUnit\Framework\TestCase;

class RedactTest extends TestCase
{
	/**
	* @dataProvider provider_disguise_masking
	* @covers Fuko\Masked\Redact::redact
	*/
	function test_disguise_masking($source, $masked)
	{
		$this->assertEquals(
			Redact::redact($source),
			$masked
		);
	}

	function provider_disguise_masking()
	{
		return array(
			array(-123, '████'),
			array(-1234, '█████'),
			array(1234, '████'),
			array(null, ''),
			array('', ''),
			array(0, '█'),
			array(00, '█'),
			array('41111111111111111', '█████████████████'),
			array([], ''),
			array((object) ['a' => 1], ''),
			array($this, ''),
		);
	}
}

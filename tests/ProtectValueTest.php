<?php

namespace Fuko\Masked\Tests;

use Fuko\Masked\Protect;
use PHPUnit\Framework\TestCase;

class ProtectValueTest extends TestCase
{
	function tearDown()
	{
		Protect::clearValues();
	}

	/**
	* @dataProvider provider_protect_value
	* @covers Fuko\Masked\Protect::protect
	* @covers Fuko\Masked\Protect::protectScalar
	*/
	function test_protect_value($haystack, $needle, $redacted)
	{
		is_scalar($needle)
			? Protect::hideValue($needle)
			: Protect::hideValues($needle);

		$this->assertEquals(
			Protect::protect($haystack),
			$redacted
		);
	}

	function provider_protect_value()
	{
		return array(
			array(
				'This is my password!',
				'password',
				'This is my ████████!'
			),
			array(
				'His name is John Doe and he is a spy',
				array('Doe', 'John'),
				'His name is ████ ███ and he is a spy'
			),
			array(
				'I\'ve had it with these motherfucking snakes on this motherfucking plane!',
				'fuck',
				'I\'ve had it with these mother████ing snakes on this mother████ing plane!'
			),
			array(
				'Your username is "condor" and your password is "NewmanSucks!"',
				array('NewmanSucks!', 'condor'),
				'Your username is "██████" and your password is "████████████"'
			),
			array(
				array(),
				array(),
				array()
			),
			array(
				array('password' => 'RedfordRulz!'),
				'RedfordRulz!',
				array('password' => '████████████')
			),
			array(
				array(
					'first' => 'Robert',
					'last' => 'Redford',
					'password' => 'RedfordRulz!',
					'email' => 'bobby@sundance.org',
				),
				'RedfordRulz!',
				array(
					'first' => 'Robert',
					'last' => 'Redford',
					'password' => '████████████',
					'email' => 'bobby@sundance.org',
				)
			),
		);
	}
}

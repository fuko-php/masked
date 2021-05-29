<?php

namespace Fuko\Masked\Tests;

use Fuko\Masked\Protect;
use Fuko\Masked\Redact;
use PHPUnit\Framework\TestCase;

class ProtectValueTest extends TestCase
{
	function tearDown(): void
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

	function test_protect_backtrace()
	{
		Protect::hideValue($username = 'u53rn4m3');
		Protect::hideValue($password = 'P422wOrd');

		$d = $this->uno($username, $password);
		$this->assertBacktrace(
			var_export(Protect::protect($d), true),
			$username,
			$password);

		$d = $this->ichi($username, $password);
		$this->assertBacktrace(Protect::protect($d), $username, $password);
	}

	function assertBacktrace($redacted, $username, $password)
	{
		$this->assertFalse(
			strpos($redacted, $username)
			);
		$this->assertFalse(
			strpos($redacted, $password)
			);

		$this->assertInternalType(
			'int',
			strpos($redacted, Redact::redact( $username ))
			);
		$this->assertInternalType(
			'int',
			strpos($redacted, Redact::redact( $password ))
			);
	}

	function uno($username, $password)
	{
		return $this->due($username, $password);
	}

	function due($username, $password)
	{
		return debug_backtrace();
	}

	function ichi($username, $password)
	{
		return $this->ni($username, $password);
	}

	function ni($username, $password)
	{
		ob_start();
		debug_print_backtrace();
		return ob_get_clean();
	}
}

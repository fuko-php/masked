<?php

namespace Fuko\Masked\Tests;

use Fuko\Masked\Protect;
use Fuko\Masked\Redact;
use PHPUnit\Framework\TestCase;

use const INPUT_POST;
use const INPUT_SERVER;
use const INPUT_SESSION;

use function strpos;
use function var_export;

class ProtectInputTest extends TestCase
{
	function tearDown(): void
	{
		Protect::clearInputs();
	}

	/**
	* @dataProvider provider_protect_input
	* @covers Fuko\Masked\Protect::protect
	* @covers Fuko\Masked\Protect::protectScalar
	*/
	function test_protect_input($name, $type, array $input)
	{
		Protect::hideInput($name, $type);
		$this->assertProtected($name, $input);
	}

	function assertProtected($name, array $input)
	{
		$redacted = var_export(Protect::protect($input), true);
		$this->assertFalse(
			strpos($redacted, $input[$name])
			);
		$this->assertIsInt(
			strpos($redacted, Redact::redact( $input[$name] ))
			);
	}

	function provider_protect_input()
	{
		return array(
			array('PHP_SELF', INPUT_SERVER, $_SERVER),
			array('USER', INPUT_SERVER, $_SERVER),
			array('user_id', INPUT_SESSION, $_SESSION = array(
				'user_id' => 4918,
				'name' => 'Waldo Pepper',
			)),
			array('password', INPUT_POST, $_POST = array(
				'username' => 'Martin.Bishop',
				'password' => 'Martin.Brice!',
			)),
		);
	}

	function test_protect_default_input()
	{
		$_SERVER['PHP_AUTH_PW'] = 'Joseph.Turner!';
		$this->assertProtected('PHP_AUTH_PW', $_SERVER);
	}
}

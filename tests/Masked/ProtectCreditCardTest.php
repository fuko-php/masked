<?php

namespace Fuko\Masked\Tests;

use Fuko\Masked\Protect;
use Fuko\Masked\Redact;
use PHPUnit\Framework\TestCase;

class ProtectCreditCardTest extends TestCase
{
	function tearDown(): void
	{
		Redact::setRedactCallback(
			array(Redact::class, 'disguise'),
			array(0, '█')
		);
	}

	/**
	 * @dataProvider provider_protect_credit_card
	 * @covers Fuko\Masked\Protect::protect
	 * @covers Fuko\Masked\Protect::protectScalar
	 */
	function test_protect_credit_card($source, $expected)
	{
		$this->assertEquals(
			$expected,
			Protect::protect($source)
		);
	}

	function provider_protect_credit_card()
	{
		return array(
			// Common card types
			//
			'Visa 16-digit' => array(
				'Card: 4111111111111111',
				'Card: ████████████████'
			),
			'Mastercard 16-digit' => array(
				'Card: 5555555555554444',
				'Card: ████████████████'
			),
			'Amex 15-digit' => array(
				'Card: 378282246310005',
				'Card: ███████████████'
			),

			// PAN length boundaries
			//
			'13-digit PAN' => array(
				'Card: 4222222222222',
				'Card: █████████████'
			),
			'19-digit PAN' => array(
				'Card: 4000000000000000006',
				'Card: ███████████████████'
			),

			// Formatted PANs
			//
			'space-separated PAN' => array(
				'Card: 4111 1111 1111 1111',
				'Card: ███████████████████'
			),
			'hyphen-separated PAN' => array(
				'Card: 4111-1111-1111-1111',
				'Card: ███████████████████'
			),
			'Amex with spaces' => array(
				'Card: 3782 822463 10005',
				'Card: █████████████████'
			),
			'19-digit PAN with spaces' => array(
				'Card: 4000 0000 0000 0000 006',
				'Card: ███████████████████████'
			),

			// PANs surrounded by other numeric data
			//
			'PAN followed by expiry date' => array(
				'Card: 4111111111111111 12/30',
				'Card: ████████████████ 12/30'
			),
			'formatted PAN followed by CVV' => array(
				'Card: 4111 1111 1111 1111 123',
				'Card: ███████████████████ 123'
			),
			'PAN followed by labelled CVV' => array(
				'Card: 4111111111111111 CVV: 003',
				'Card: ████████████████ CVV: 003'
			),
			'number before formatted PAN' => array(
				'Order 123 4111 1111 1111 1111',
				'Order 123 ███████████████████'
			),

			// Multiple PANs
			//
			'multiple PANs separated by text' => array(
				'Primary: 4111111111111111, backup: 5555555555554444',
				'Primary: ████████████████, backup: ████████████████'
			),
			'adjacent PANs' => array(
				'Cards: 4111111111111111 5555555555554444',
				'Cards: ████████████████ ████████████████'
			),
		);
	}

	/**
	 * @dataProvider provider_ignore_non_credit_card
	 * @covers Fuko\Masked\Protect::protect
	 * @covers Fuko\Masked\Protect::protectScalar
	 */
	function test_ignore_non_credit_card($source)
	{
		$this->assertEquals(
			$source,
			Protect::protect($source)
		);
	}

	function provider_ignore_non_credit_card()
	{
		return array(
			'invalid Luhn checksum' => array(
				'Card: 4111111111111112'
			),
			'arbitrary numeric reference' => array(
				'Reference: 1234567890123456'
			),
			'repeated digits' => array(
				'Reference: 0000000000000000'
			),
			'20-digit numeric reference' => array(
				'Reference: 12345678901234567894'
			),
			'20-digit reference containing valid PAN prefix' => array(
				'Reference: 41111111111111111234'
			),
		);
	}

	/**
	 * @dataProvider provider_preserve_non_string_scalar
	 * @covers Fuko\Masked\Protect::protect
	 * @covers Fuko\Masked\Protect::protectScalar
	 */
	function test_preserve_non_string_scalar($value)
	{
		$this->assertSame(
			$value,
			Protect::protect($value)
		);
	}

	function provider_preserve_non_string_scalar()
	{
		return array(
			'integer' => array(12345),
			'float' => array(12.34),
			'true' => array(true),
			'false' => array(false),
		);
	}

	/**
	 * @covers Fuko\Masked\Protect::protect
	 * @covers Fuko\Masked\Protect::protectScalar
	 */
	function test_protect_credit_card_inside_nested_array()
	{
		$this->assertEquals(
			array(
				'payment' => array(
					'card' => '████████████████',
				),
				'reference' => '1234567890123456',
			),
			Protect::protect(array(
				'payment' => array(
					'card' => '4111111111111111',
				),
				'reference' => '1234567890123456',
			))
		);
	}

	/**
	 * @covers Fuko\Masked\Protect::protect
	 * @covers Fuko\Masked\Protect::protectScalar
	 */
	function test_protect_credit_card_uses_redact_callback()
	{
		Redact::setRedactCallback(
			array(Redact::class, 'disguise'),
			array(4, '*')
		);

		$this->assertEquals(
			'Card: ************1111',
			Protect::protect('Card: 4111111111111111')
		);
	}
}

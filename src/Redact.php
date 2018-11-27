<?php /**
* Fuko\Masked: uncover and mask sensitive data
*
* @category Fuko
* @package Fuko\Masked
*
* @author Kaloyan Tsvetkov (KT) <kaloyan@kaloyan.info>
* @link https://github.com/fuko-php/masked/
* @license https://opensource.org/licenses/MIT
*/

namespace Fuko\Masked;

/**
* Masks sensitive data: replaces blacklisted elements with redacted values
*
* @package Fuko\Masked
*/
class Redact
{
	/**
	* @var array callback used in {@link Fuko\Masked\Redact::redact()};
	*	format is actually an array with two elements, first being
	*	the actual callback, and the second being an array with any
	*	extra arguments that are needed.
	*/
	protected static $redactCallback = array(
		array(__CLASS__, 'disguise'),
		array(0, '█')
	);

	/**
	* Redacts provided string by masking it
	*
	* @param string $value
	* @return string
	*/
	public static function redact($value)
	{
		$args = self::$redactCallback[1];
		array_unshift($args, $value);

		return call_user_func_array(
			self::$redactCallback[0],
			$args
		);
	}

	/**
	* Set a new callback to be used by {@link Fuko\Masked\Redact::redact()}
	*
	* First callback argument will be the value that needs to be
	* masked/redacted; optionally you can provide more $arguments
	*
	* @param callable $callback
	* @param array $arguments (optional) extra arguments for the callback
	* @throws \InvalidArgumentException
	*/
	public static function setRedactCallback($callback, array $arguments = null)
	{
		if (!is_callable($callback))
		{
			throw new \InvalidArgumentException(
				'First argument to '
					. __METHOD__
					. '() must be a valid callback'
			);
		}

		self::$redactCallback = array(
			$callback,
			!empty($arguments)
				? array_values($arguments)
				: array()
		);
	}

	/**
	* Get a masked version of a string
	*
	* This is the default callback used by {@link Fuko\Masked\Redact::redact()}
	*
	* @param string	$value
	* @param integer $unmaskedChars number of chars to mask; having
	*	positive number will leave the unmasked symbols at the
	*	end of the value; using negative number will leave the
	*	unmasked chars at the start of the value
	* @param string $maskSymbol
	* @return string
	*/
	public static function disguise($value, $unmaskedChars = 4, $maskSymbol = '*')
	{
		$value = filter_var($value, FILTER_SANITIZE_STRING);
		$unmaskedChars = (int) $unmaskedChars;
		$maskSymbol = filter_var($maskSymbol, FILTER_SANITIZE_STRING);

		/* not enough chars to unmask ? */
		if (abs($unmaskedChars) >= strlen($value))
		{
			$unmaskedChars = 0;
		}

		/* at least half must be masked ? */
		if (abs($unmaskedChars) > strlen($value)/2)
		{
			$unmaskedChars = round($unmaskedChars/2);
		}

		/* leading unmasked chars */
		if ($unmaskedChars < 0)
		{
			$unmasked = substr($value, 0, -$unmaskedChars);
			return $unmasked . str_repeat($maskSymbol,
				strlen($value) - strlen($unmasked)
				);
		}

		/* trailing unmasked chars */
		$unmasked = $unmaskedChars
			? substr($value, -$unmaskedChars)
			: '';
		return str_repeat($maskSymbol,
			strlen($value) - strlen($unmasked)
			) . $unmasked;
	}
}

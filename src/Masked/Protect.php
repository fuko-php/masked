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

use Fuko\Masked\InputCollection;
use Fuko\Masked\ValueCollection;

use const FILTER_DEFAULT;
use const PREG_OFFSET_CAPTURE;

use function count;
use function filter_var;
use function is_array;
use function is_object;
use function is_scalar;
use function preg_match;
use function preg_match_all;
use function preg_replace_callback;
use function strlen;
use function strpos;
use function str_replace;
use function substr;

/**
* Protect sensitive data and redacts it using {@link Fuko\Masked\Redact::redact()}
*
* @package Fuko\Masked
*/
final class Protect
{
	/**
	* @var ValueCollection collection of values to hide redacting
	*/
	private static $hideValueCollection;

	/**
	* Clear accumulated values to hide
	*/
	static function clearValues()
	{
		if (!empty(self::$hideValueCollection))
		{
			self::$hideValueCollection->clearValues();
		}
	}

	/**
	* Introduce new values to hide
	*
	* @param array $values array with values of scalars or
	*	objects that have __toString() methods
	*/
	static function hideValues(array $values)
	{
		(self::$hideValueCollection
			?? (self::$hideValueCollection =
				new ValueCollection))->hideValues($values);
	}

	/**
	* Introduce a new value to hide
	*
	* @param mixed $value scalar values (strings, numbers)
	*	or objects with __toString() method added
	* @return boolean|NULL TRUE if added, FALSE if wrong
	*	type, NULL if already added
	*/
	static function hideValue($value)
	{
		return (self::$hideValueCollection
			?? (self::$hideValueCollection =
				new ValueCollection))->hideValue($value);
	}

	/////////////////////////////////////////////////////////////////////

	/**
	* @var InputCollection collection of inputs for scanning to find
	*	values for redacting
	*/
	private static $hideInputCollection;

	/**
	* Clear accumulated inputs to hide
	*/
	static function clearInputs()
	{
		if (!empty(self::$hideInputCollection))
		{
			self::$hideInputCollection->clearInputs();
		}
	}

	/**
	* Introduce new inputs to hide
	*
	* @param array $inputs array keys are input types(INPUT_REQUEST,
	*	INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SESSION,
	*	INPUT_SERVER, INPUT_ENV), array values are arrays with
	*	input names
	*/
	static function hideInputs(array $inputs)
	{
		(self::$hideInputCollection
			?? (self::$hideInputCollection =
				new InputCollection))->hideInputs($inputs);
	}

	/**
	* Introduce a new input to hide
	*
	* @param string $name input name, e.g. "password" if you are
	*	targeting $_POST['password']
	* @param integer $type input type, must be one of these: INPUT_REQUEST,
	*	INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SESSION, INPUT_SERVER,
	*	INPUT_ENV; default value is INPUT_REQUEST
	* @return boolean|NULL TRUE if added, FALSE if wrong
	*	name or type, NULL if already added
	*/
	static function hideInput($name, $type = INPUT_REQUEST)
	{
		return (self::$hideInputCollection
			?? (self::$hideInputCollection =
				new InputCollection))->hideInput($name, $type);
	}

	/////////////////////////////////////////////////////////////////////

	/**
	* Protects a variable by replacing sensitive data inside it
	*
	* @param mixed $var only strings and arrays will be processed,
	*	objects will be "stringified", other types (resources?)
	*	will be returned as empty strings
	* @return string|array
	*/
	static function protect($var)
	{
		if (is_scalar($var))
		{
			return self::protectScalar($var);
		} else
		if (is_array($var))
		{
			foreach ($var as $k => $v)
			{
				$var[$k] = self::protect($v);
			}

			return $var;
		} else
		if (is_object($var))
		{
			return self::protectScalar(
				filter_var($var, FILTER_DEFAULT)
			);
		} else
		{
			return '';
		}
	}

	/**
	* Protects a scalar value by replacing sensitive data inside it
	*
	* @param string $var
	* @return string
	*/
	static function protectScalar($var)
	{
		// hide values
		//
		if (!empty(self::$hideValueCollection))
		{
			if ($hideValues = self::$hideValueCollection->getValues())
			{
				$var = self::_redact($var, $hideValues);
			}
		}

		// hide inputs
		//
		$hideInputValues = array();
		if (!empty(self::$hideInputCollection))
		{
			$hideInputValues = self::$hideInputCollection->getInputsValues();
			if (!empty($hideInputValues))
			{
				$var = self::_redact($var, $hideInputValues);
			}
		}

		return self::_redactCreditCards($var);
	}

	/**
	* Detects and redacts credit card numbers inside a string
	*
	* @param string $var
	* @return string
	*/
	private static function _redactCreditCards($var)
	{
		$string = (string) $var;
		$redacted = preg_replace_callback(
			'~(?<!\d)\d+(?:[ -]\d+)*(?!\d)~',
			static function ($matches)
			{
				return self::_redactCreditCardSequence($matches[0]);
			},
			$string
		);
		return $redacted === $string
			? $var
			: $redacted;
	}

	/**
	 * Detects and redacts credit card numbers inside a numeric sequence
	 *
	 * @param string $value
	 * @return string
	 */
	private static function _redactCreditCardSequence($value)
	{
		preg_match_all(
			'~\d+~',
			$value,
			$matches,
			PREG_OFFSET_CAPTURE
		);

		$groups = $matches[0];
		$count = count($groups);
		$redacted = '';
		$offset = 0;
		$groupIndex = 0;

		while ($groupIndex < $count)
		{
			$start = $groups[$groupIndex][1];
			$digitsLength = 0;
			$match = NULL;

			for (
				$candidateIndex = $groupIndex;
				$candidateIndex < $count;
				$candidateIndex++
			)
			{
				$groupLength = strlen($groups[$candidateIndex][0]);
				$digitsLength += $groupLength;

				if ($digitsLength > 19)
				{
					break;
				}

				if ($digitsLength < 13)
				{
					continue;
				}

				$end = $groups[$candidateIndex][1] + $groupLength;
				$candidate = substr(
					$value,
					$start,
					$end - $start
				);

				if (self::_isCreditCard($candidate))
				{
					$match = array(
						$start,
						$end,
						$candidateIndex
					);
				}
			}

			if (NULL === $match)
			{
				$groupIndex++;
				continue;
			}

			$redacted .= substr(
				$value,
				$offset,
				$match[0] - $offset
			);

			$redacted .= Redact::redact(
				substr(
					$value,
					$match[0],
					$match[1] - $match[0]
				)
			);

			$offset = $match[1];
			$groupIndex = $match[2] + 1;
		}

		return 0 === $offset
			? $value
			: $redacted . substr($value, $offset);
	}

	/**
	* Checks whether a value is a valid credit card number
	* using the Luhn checksum
	*
	* @param string $value
	* @return boolean
	*/
	private static function _isCreditCard($value)
	{
		$number = str_replace(array(' ', '-'), '', $value);
		$length = strlen($number);

		if ($length < 13 || $length > 19)
		{
			return false;
		}

		if (preg_match('~^(\d)\1+$~', $number))
		{
			return false;
		}

		$sum = 0;
		$parity = $length % 2;

		for ($index = 0; $index < $length; $index++)
		{
			$digit = (int) $number[$index];

			if ($index % 2 === $parity)
			{
				$digit *= 2;

				if ($digit > 9)
				{
					$digit -= 9;
				}
			}

			$sum += $digit;
		}

		return 0 === $sum % 10;
	}

	/**
	* Redacts $values inside the $var string
	* @param string $var
	* @param array $values
	* @return string
	*/
	private static function _redact($var, array $values)
	{
		foreach ($values as $value)
		{
			$value = (string) $value;
			if (false === strpos($var, $value))
			{
				continue;
			}

			$var = str_replace($value, Redact::redact($value), $var);
		}

		return $var;
	}
}

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

use function filter_var;
use function is_array;
use function is_object;
use function is_scalar;
use function strpos;
use function str_replace;

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

		return $var;
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

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

use const FILTER_SANITIZE_STRING;
use const E_USER_WARNING;
use const INPUT_ENV;
use const INPUT_SERVER;
use const INPUT_COOKIE;
use const INPUT_GET;
use const INPUT_POST;
use const INPUT_SESSION;
use const INPUT_REQUEST;

use function array_keys;
use function define;
use function defined;
use function gettype;
use function filter_var;
use function in_array;
use function is_array;
use function is_callable;
use function is_object;
use function is_scalar;
use function sprintf;
use function strpos;
use function str_replace;
use function trigger_error;

if (!defined('INPUT_SESSION'))
{
	define('INPUT_SESSION', 6);
}

if (!defined('INPUT_REQUEST'))
{
	define('INPUT_REQUEST', 99);
}

/**
* Protect sensitive data and redacts it using {@link Fuko\Masked\Redact::redact()}
*
* @package Fuko\Masked
*/
class Protect
{
	/**
	* @var array collection of values to hide redacting
	*/
	private static $hideValues = array();

	/**
	* Clear accumulated values to hide
	*/
	public static function clearValues()
	{
		self::$hideValues = array();
	}

	/**
	* Introduce new values to hide
	*
	* @param array $values array with values of scalars or
	*	objects that have __toString() methods
	*/
	public static function hideValues(array $values)
	{
		foreach ($values as $k => $value)
		{
			if (self::_validateValue(
				$value, __METHOD__
					. '() received %s as a hide value (key "'
					. $k . '" of the $values argument)'))
			{
				if (!in_array($value, self::$hideValues))
				{
					self::$hideValues[] = $value;
				}
			}
		}
	}

	/**
	* Introduce a new value to hide
	*
	* @param mixed $value scalar values (strings, numbers)
	*	or objects with __toString() method added
	* @return boolean|NULL TRUE if added, FALSE if wrong
	*	type, NULL if already added
	*/
	public static function hideValue($value)
	{
		if (!self::_validateValue(
			$value,
			__METHOD__ . '() received %s as a hide value')
			)
		{
			return false;
		}

		if (in_array($value, self::$hideValues))
		{
			return null;
		}

		self::$hideValues[] = $value;
		return true;
	}

	/**
	* Validate $value:
	* 	- check if it is empty,
	*	- if it is string or if an object with __toString() method
	* @param mixed $value
	* @param string $error error message placeholder
	* @return boolean
	*/
	private static function _validateValue($value, $error = '%s')
	{
		if (empty($value))
		{
			$wrongType = 'an empty value';
		} else
		if (is_scalar($value))
		{
			$wrongType = '';
		} else
		if (is_array($value))
		{
			$wrongType = 'an array';
		} else
		if (is_object($value))
		{
			$wrongType = !is_callable(array($value, '__toString'))
				? 'an object'
				: '';
		} else
		{
			/* resources ? */
			$wrongType = 'unexpected type (' . (string) $value . ')';
		}

		if ($wrongType)
		{
			trigger_error(
				sprintf($error, $wrongType),
				E_USER_WARNING
				);

			return false;
		}

		return true;
	}

	/////////////////////////////////////////////////////////////////////

	/**
	* @var array collection of inputs for scanning to find
	*	values for redacting
	*/
	private static $hideInputs = array();

	/**
	* Clear accumulated inputs to hide
	*/
	public static function clearInputs()
	{
		self::$hideInputs = array();
	}

	/**
	* Introduce new inputs to hide
	*
	* @param array $inputs array keys are input types(INPUT_REQUEST,
	*	INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SESSION,
	*	INPUT_SERVER, INPUT_ENV), array values are arrays with
	*	input names
	*/
	public static function hideInputs(array $inputs)
	{
		foreach ($inputs as $type => $names)
		{
			if (empty($names))
			{
				trigger_error( __METHOD__
					. '() empty input names for "'
					. $type . '" input type',
					E_USER_WARNING
					);
				continue;
			}

			if (is_scalar($names))
			{
				$names = array(
					$names
					);
			}

			if (!is_array($names))
			{
				trigger_error( __METHOD__
					. '() input names must be string or array, '
					. gettype($names) . ' provided instead',
					E_USER_WARNING
					);
				continue;
			}

			foreach ($names as $name)
			{
				if (self::_validateInput($name, $type, __METHOD__))
				{
					if (!isset(self::$hideInputs[$type][$name]))
					{
						self::$hideInputs[$type][$name] = true;
					}
				}
			}
		}
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
	public static function hideInput($name, $type = INPUT_REQUEST)
	{
		if (!self::_validateInput($name, $type, __METHOD__))
		{
			return false;
		}

		if (isset(self::$hideInputs[$type][$name]))
		{
			return null;
		}

		return self::$hideInputs[$type][$name] = true;
	}

	/**
	* Validates input:
	*	- if $name is empty
	*	- if $name is scalar
	*	- if $type is scalar
	*	- if $type is one of these: INPUT_REQUEST,
	*		INPUT_GET, INPUT_POST, INPUT_COOKIE,
	*		INPUT_SESSION, INPUT_SERVER, INPUT_ENV
	* @param string $name
	* @param integer $type
	* @param string $method method to use to report the validation errors
	* @return boolean
	*/
	private static function _validateInput($name, &$type, $method)
	{
		if (empty($name))
		{
			trigger_error(
				$method . '() $name argument is empty',
				E_USER_WARNING
				);
			return false;
		}

		if (!is_scalar($name))
		{
			trigger_error(
				$method . '() $name argument is not scalar, it is '
					. gettype($name),
				E_USER_WARNING
				);
			return false;
		}

		if (!is_scalar($type))
		{
			trigger_error(
				$method . '() $type argument is not scalar, it is '
					. gettype($type),
				E_USER_WARNING
				);
			return false;
		}

		$type = (int) $type;
		if (!in_array($type, array(
			INPUT_REQUEST,
			INPUT_GET,
			INPUT_POST,
			INPUT_COOKIE,
			INPUT_SESSION,
			INPUT_SERVER,
			INPUT_ENV)))
		{
			$type = INPUT_REQUEST;
		}

		return true;
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
	public static function protect($var)
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
	public static function protectScalar($var)
	{
		// hide values
		//
		if (!empty(self::$hideValues))
		{
			$var = self::_redact($var, self::$hideValues);
		}

		// default hideInputs values ?
		//
		if (empty(self::$hideInputs))
		{
			self::$hideInputs = array(
				INPUT_SERVER => array(
					'PHP_AUTH_PW' => true
					),
				INPUT_POST => array(
					'password' => true
					),
				);
		}

		// hide inputs
		//
		$hideInputValues = array();
		foreach (self::$hideInputs as $type => $inputs)
		{
			// the input names are the keys
			//
			foreach (array_keys($inputs) as $name)
			{
				$input = self::_filter_input($type, $name);
				if (!$input)
				{
					continue;
				}

				$hideInputValues[] = $input;
 			}
		}

		if (!empty($hideInputValues))
		{
			$var = self::_redact($var, $hideInputValues);
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

	/**
	* Gets a specific external variable by name and filter it as a string
	*
	* @param integer $type input type, must be one of INPUT_REQUEST,
	*	INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SESSION,
	*	INPUT_SERVER or INPUT_ENV
	* @param string $name name of the input variable to get
	* @return string
	*/
	private static function _filter_input($type, $name)
	{
		switch ($type)
		{
			case INPUT_ENV :
				return !empty($_ENV)
					? self::_filter_var($_ENV, $name)
					: '';

			case INPUT_SERVER :
				return !empty($_SERVER)
					? self::_filter_var($_SERVER, $name)
					: '';

			case INPUT_COOKIE :
				return !empty($_COOKIE)
					? self::_filter_var($_COOKIE, $name)
					: '';

			case INPUT_GET :
				return !empty($_GET)
					? self::_filter_var($_GET, $name)
					: '';

			case INPUT_POST :
				return !empty($_POST)
					? self::_filter_var($_POST, $name)
					: '';

			case INPUT_SESSION :
				return !empty($_SESSION)
					? self::_filter_var($_SESSION, $name)
					: '';

			case INPUT_REQUEST :
				return !empty($_REQUEST)
					? self::_filter_var($_REQUEST, $name)
					: '';

			default: return '';
		}
	}

	/**
	* Filters a variable as a string
	*
	* @param array $input
	* @param string $name name of the input variable to get
	* @return string
	*/
	private static function _filter_var(array $input, $name)
	{
		if (empty($input[$name]))
		{
			return '';
		}

		return filter_var(
			$input[$name],
			FILTER_SANITIZE_STRING
		);
	}
 }

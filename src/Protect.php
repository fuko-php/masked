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
* Protect sensitive data and redacts it using {@link Fuko\Masked\Redact::redact()}
*
* @package Fuko\Masked
*
* @todo when protecting, inspect for escapeable chars and look for variations ?
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
	static function clearValues()
	{
		self::$hideValues = array();
	}

	/**
	* Introduce new values to hide
	*
	* @param array $values array with values of scalars or
	*	objects that have __toString() methods
	*/
	static function hideValues(array $values)
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
	static function hideValue($value)
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
	static function clearInputs()
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
	static function hideInputs(array $inputs)
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
	static function hideInput($name, $type = INPUT_REQUEST)
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
	* @param mixed $var only strings and arrays will be processed, other
	*	types will be returned as they are
	* @return string
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
		{
			return $var;
		}
	}

	/**
	* Protects a scalar value by replacing sensitive data inside it
	* @param string $var
	* @return string
	*/
	static function protectScalar($var)
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
					'PHP_AUTH_PW' => 1
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
			$inputArray = array(1);
			self::_inputArray($type, $inputArray);

			// the input names are the keys
			//
			foreach (array_keys($inputs) as $input)
			{
				if (empty($inputArray[ $input ]))
				{
					continue;
				}

				if (!is_scalar($inputArray[ $input ]))
				{
					continue;
				}

				$hideInputValues[] = $inputArray[ $input ];
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
	* Get an input array
	* @param integer $type
	* @param array &$inputArray
	*/
	private static function _inputArray($type, array &$inputArray)
	{
		if ((INPUT_POST == $type) && !empty($_POST))
		{
			$inputArray = $_POST;
		} else
		if ((INPUT_GET == $type) && !empty($_GET))
		{
			$inputArray = $_GET;
		} else
		if ((INPUT_COOKIE == $type) && !empty($_COOKIE))
		{
			$inputArray = $_COOKIE;
		} else
		if ((INPUT_SESSION == $type) && !empty($_SESSION))
		{
			$inputArray = $_SESSION;
		} else
		if ((INPUT_SERVER == $type) && !empty($_SERVER))
		{
			$inputArray = $_SERVER;
		} else
		if ((INPUT_ENV == $type) && !empty($_ENV))
		{
			$inputArray = $_ENV;
		} else
		if (INPUT_REQUEST == $type)
		{
			// collect $_REQUEST yourself since input
			// arrays might have been changed after the
			// script has started
			//
			static $request_order;
			if (!$request_order)
			{
				$request_order = ini_get('request_order')
					OR $request_order = 'GP';
			}

			$inputArray = array();
			for ($i = 0; $i < strlen($request_order); $i++)
			{
				$inputArray = array_merge(
					$inputArray,
					('G' == $request_order[$i]
						? $_GET
						: ('P' == $request_order[$i]
							? $_POST
							: ('C' == $request_order[$i]
								? $_COOKIE
								: array()
								)
							)
						)
					);
			}
		}
	}
}

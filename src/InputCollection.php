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
* Collects inputs for scanning to find values for redacting with {@link Fuko\Masked\Protect}
*
* @package Fuko\Masked
*/
class InputCollection
{
	/**
	* @var array default inputs to scan
	*/
	const default_inputs = array(
		INPUT_SERVER => array(
			'PHP_AUTH_PW' => true
			),
		INPUT_POST => array(
			'password' => true
			),
		);

	/**
	* @var array collection of inputs for scanning to find values for redacting
	*/
	protected $hideInputs = self::default_inputs;

	/**
	* Clear accumulated inputs to hide
	*/
	function clearInputs()
	{
		$this->hideInputs = self::default_inputs;
	}

	/**
	* Get list of accumulated inputs to hide
	*
	* @return array
	*/
	function getInputs()
	{
		return $this->hideInputs;
	}

	/**
	* Get the actual input values to hide
	*
	* @return array
	*/
	function getInputsValues()
	{
		$hideInputValues = array();
		foreach ($this->hideInputs as $type => $inputs)
		{
			// the input names are the keys
			//
			foreach (array_keys($inputs) as $name)
			{
				$input = $this->filterInput($type, $name);
				if (!$input)
				{
					continue;
				}

				$hideInputValues[] = $input;
 			}
		}

		return $hideInputValues;
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
	protected function filterInput($type, $name)
	{
		switch ($type)
		{
			case INPUT_ENV :
				return !empty($_ENV)
					? $this->filterInputVar($_ENV, $name)
					: '';

			case INPUT_SERVER :
				return !empty($_SERVER)
					? $this->filterInputVar($_SERVER, $name)
					: '';

			case INPUT_COOKIE :
				return !empty($_COOKIE)
					? $this->filterInputVar($_COOKIE, $name)
					: '';

			case INPUT_GET :
				return !empty($_GET)
					? $this->filterInputVar($_GET, $name)
					: '';

			case INPUT_POST :
				return !empty($_POST)
					? $this->filterInputVar($_POST, $name)
					: '';

			case INPUT_SESSION :
				return !empty($_SESSION)
					? $this->filterInputVar($_SESSION, $name)
					: '';

			case INPUT_REQUEST :
				return !empty($_REQUEST)
					? $this->filterInputVar($_REQUEST, $name)
					: '';
		}

		return '';
	}

	/**
	* Filters a variable as a string
	*
	* @param array $input
	* @param string $name name of the input variable to get
	* @return string
	*/
	protected function filterInputVar(array $input, $name)
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

	/**
	* Introduce new inputs to hide
	*
	* @param array $inputs array keys are input types(INPUT_REQUEST,
	*	INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SESSION,
	*	INPUT_SERVER, INPUT_ENV), array values are arrays with
	*	input names
	*/
	function hideInputs(array $inputs)
	{
		foreach ($inputs as $type => $names)
		{
			if (empty($names))
			{
				trigger_error('Fuko\Masked\Protect::hideInputs'
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
				trigger_error('Fuko\Masked\Protect::hideInputs'
					. '() input names must be string or array, '
					. gettype($names) . ' provided instead',
					E_USER_WARNING
					);
				continue;
			}

			foreach ($names as $name)
			{
				$this->addInput($name, $type, 'Fuko\Masked\Protect::hideInputs');
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
	function hideInput($name, $type = INPUT_REQUEST)
	{
		return $this->addInput($name, $type, 'Fuko\Masked\Protect::hideInput');
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
	protected function validateInput($name, &$type, $method)
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

	/**
	* Add $input as of $type to the list of inputs to hide
	*
	* @param string $name input name, e.g. "password" if you are
	*	targeting $_POST['password']
	* @param integer $type input type, must be one of these: INPUT_REQUEST,
	*	INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SESSION, INPUT_SERVER,
	*	INPUT_ENV; default value is INPUT_REQUEST
	* @param string $method method to use to report the validation errors
	* @return boolean|NULL TRUE if added, FALSE if wrong
	*	name or type, NULL if already added
	*/
	protected function addInput($name, $type, $method)
	{
		if (!$this->validateInput($name, $type, $method))
		{
			return false;
		}

		if (isset($this->hideInputs[$type][$name]))
		{
			return null;
		}

		return $this->hideInputs[$type][$name] = true;
	}
}

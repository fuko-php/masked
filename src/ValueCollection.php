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

use const E_USER_WARNING;

use function gettype;
use function in_array;
use function is_array;
use function is_callable;
use function is_object;
use function is_scalar;
use function sprintf;
use function trigger_error;

/**
* Collects values to uncover and mask with {@link Fuko\Masked\Protect}
*
* @package Fuko\Masked
*/
class ValueCollection
{
	/**
	* @var array collection of values to hide redacting
	*/
	protected $hideValues = array();

	/**
	* Get the collected values to hide
	*
	* @return array
	*/
	function getValues()
	{
		return $this->hideValues;
	}

	/**
	* Clear accumulated values to hide
	*/
	function clearValues()
	{
		$this->hideValues = array();
	}

	/**
	* Introduce new values to hide
	*
	* @param array $values array with values of scalars or
	*	objects that have __toString() methods
	*/
	function hideValues(array $values)
	{
		foreach ($values as $k => $value)
		{
			$this->addValue(
				$value, 'Fuko\Masked\Protect::hideValues'
					. '() received %s as a hide value (key "'
					. $k . '" of the $values argument)');
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
	function hideValue($value)
	{
		return $this->addValue(
			$value,
			'Fuko\Masked\Protect::hideValue() received %s as a hide value'
			);
	}

	/**
	* Validate $value:
	* 	- check if it is empty,
	*	- if it is string or if an object with __toString() method
	* @param mixed $value
	* @param string $error error message placeholder
	* @return boolean
	*/
	protected function validateValue($value, $error)
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

	/**
	* Add $value to the list of values to hide
	*
	* @param mixed $value
	* @param string $error error message placeholder
	* @return boolean|NULL TRUE if added, FALSE if wrong
	*	type, NULL if already added
	*/
	protected function addValue($value, $error = '%s')
	{
		if (!$this->validateValue($value, $error))
		{
			return false;
		}

		if (in_array($value, $this->hideValues))
		{
			return null;
		}

		$this->hideValues[] = $value;
		return true;
	}
}

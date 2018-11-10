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
* Discovers sensitive data and redacts it using {@link Fuko\Masked\Redact::redact()}
*
* @package Fuko\Masked
*/
class Uncover
{
	/**
	* Uncover behaviour: find exact match
	*/
	const UNCOVER_EXACT = 0;

	/**
	* Uncover behaviour: find occurences
	*/
	const UNCOVER_CONTAINS = 1;

	/**
	* Finds and redacts the $needle elements inside the $haystack
	* using {@link Fuko\Masked\Redact::redact()}
	*
	* @param mixed $haystack
	* @param string $needle
	* @param string $behaviour (optional) what type of search to do;
	*	available options are {@link Fuko\Masked\Uncover::UNCOVER_EXACT}
	*	for exact matches, and {@link Fuko\Masked\Uncover::UNCOVER_CONTAINS}
	*	for finding occurences
	* @return mixed
	*/
	public static function uncover($haystack, $needle, $behaviour = self::UNCOVER_EXACT)
	{
		if (empty($haystack))
		{
			return $haystack;
		}

		if (!in_array($behaviour, array(
			self::UNCOVER_EXACT,
			self::UNCOVER_CONTAINS
		)))
		{
			trigger_error(
				"Unknown uncover behaviour {$behaviour}, will use "
					. __CLASS__ . "::UNCOVER_EXACT instead",
				E_USER_WARNING);

			$behaviour = self::UNCOVER_EXACT;
		}

		$uncoverMethod = (self::UNCOVER_CONTAINS == $behaviour
			? 'uncoverContains'
			: 'uncoverExact'
			);

		if (is_scalar($haystack))
		{
			return self::exposeScalar(
				$haystack,
				$needle,
				$uncoverMethod);
		} else
		if (is_array($haystack) || is_object($haystack))
		{
			return self::exposeTraverse(
				$haystack,
				$needle,
				$uncoverMethod);
		}

		return $haystack;
	}

	/**
	* Akwardly, mask occurences if found inside the $haystack
	*
	* @param mixed $haystack
	* @param string $needle
	* @param string $uncoverMethod
	* @return string
	*/
	private static function exposeScalar($haystack, $needle, $uncoverMethod)
	{
		if (static::$uncoverMethod($haystack, $needle))
		{
			return str_replace(
				$needle,
				Redact::redact($needle),
				$haystack);
		}

		return $haystack;
	}

	/**
	* Traverse arrays or public object properties to find what to mask
	*
	* @param array|object $haystack
	* @param string $needle
	* @param string $uncoverMethod
	* @return array|object
	*/
	private static function exposeTraverse($haystack, $needle, $uncoverMethod)
	{
		if ($o = is_object($haystack))
		{
			$vars = get_object_vars($haystack);
		} else
		{
			$vars = $haystack;
		}

		if (empty($vars))
		{
			return $haystack;
		}

		foreach ($vars as $key => $value)
		{
			if (static::$uncoverMethod($key, $needle))
			{
				$value = Redact::redact(
					is_scalar($value)
						? $value
						: is_array($value)
							? 'Array'
							: (string) $value
					);
			} else
			if (!is_scalar($value))
			{
				 $value = self::exposeTraverse(
					$value,
					$needle,
					$uncoverMethod
					);
			}

			$o ? $haystack->$key = $value
			   : $haystack[$key] = $value;
		}

		return $haystack;
	}

	/**
	* Returns TRUE if the $haystack and $needle are equal
	*
	* This method is used for the {@link Fuko\Masked\Uncover::UNCOVER_EXACT} behaviour
	*
	* @param mixed $haystack
	* @param string $needle
	* @return boolean
	*/
	protected static function uncoverExact($haystack, $needle)
	{
		return 0 == strcmp($haystack, $needle);
	}

	/**
	* Returns TRUE if there are occurences of $needle inside $haystack
	*
	* This method is used for the {@link Fuko\Masked\Uncover::UNCOVER_CONTAINS} behaviour
	*
	* @param mixed $haystack
	* @param string $needle
	* @return boolean
	*/
	protected static function uncoverContains($haystack, $needle)
	{
		return false !== strstr($haystack, $needle);
	}
}

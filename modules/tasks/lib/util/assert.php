<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Util;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Assert
{
	// checkers

	/**
	* Method checks if the given argument is an integer value, or can be casted to it. False and '' also allowed
	*
	* @param mixed $arg Argument to check.
	* @param string $argName Aargument name to figure in a error message.
	* @param string $customMsg Custom message to be shown instead of a standard one.
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return integer checked and casted value
	*/
	public final static function expectInteger($arg, $argName = '', $customMsg = '')
	{
		if((string) $arg == '' || $arg === false)
			return 0;

		$argInt = intval($arg);
		if((string) $arg !== (string) $argInt)
			throw new Main\ArgumentException(self::formMessage('TASKS_ASSERT_INTEGER_EXPECTED', $argName, $customMsg));

		return $argInt;
	}

	/**
	* Method checks if the given argument is a positive integer value, or can be casted to it
	*
	* @param mixed $arg Argument to check.
	* @param string $argName Argument name to figure in a error message.
	* @param string $customMsg Custom message to be shown instead of a standard one.
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return integer checked and casted value
	*/
	public final static function expectIntegerPositive($arg, $argName = '', $customMsg = '')
	{
		$argInt = intval($arg);
		if(((string) $arg !== (string) $argInt) || $argInt <= 0)
			throw new Main\ArgumentException(self::formMessage('TASKS_ASSERT_INTEGER_NOTNULL_EXPECTED', $argName, $customMsg));

		return $argInt;
	}

	/**
	* Method checks if the given argument is a non-negative integer value, or can be casted to it. False and '' also allowed
	*
	* @param mixed $arg Argument to check.
	* @param string $argName Argument name to figure in a error message.
	* @param string $customMsg Custom message to be shown instead of a standard one.
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return integer checked and casted value
	*/
	public final static function expectIntegerNonNegative($arg, $argName = '', $customMsg = '')
	{
		if((string) $arg == '' || $arg === false)
			return 0;

		$argInt = intval($arg);
		if(((string) $arg !== (string) $argInt) || $argInt < 0)
			throw new Main\ArgumentException(self::formMessage('TASKS_ASSERT_INTEGER_NONNEGATIVE_EXPECTED', $argName, $customMsg));

		return $argInt;
	}

	/**
	* Method checks if the given argument is a non-zero-length string value, or can be casted to it
	*
	* @param mixed $arg Argument to check.
	* @param string $argName Argument name to figure in a error message.
	* @param string $customMsg Custom message to be shown instead of a standard one.
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return string checked and casted value
	*/
	public final static function expectStringNotNull($arg, $argName = '', $customMsg = '')
	{
		if(!strlen($arg))
			throw new Main\ArgumentException(self::formMessage('TASKS_ASSERT_STRING_NOTNULL_EXPECTED', $argName, $customMsg));

		return (string) $arg;
	}

	/**
	* Method checks if the given argument is an array
	*
	* @param mixed[] $arg Argument to check.
	* @param string $argName Argument name to figure in a error message.
	* @param string $customMsg Custom message to be shown instead of a standard one.
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return mixed[] value being checked
	*/
	public final static function expectArray($arg, $argName = '', $customMsg = '')
	{
		if(!is_array($arg))
			throw new Main\ArgumentException(self::formMessage('TASKS_ASSERT_ARRAY_EXPECTED', $argName, $customMsg));

		return $arg;
	}

	/**
	* Method checks if the given argument is a non-empty array
	*
	* @param mixed[] $arg Argument to check.
	* @param string $argName Argument name to figure in a error message.
	* @param string $customMsg Custom message to be shown instead of a standard one.
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return mixed[] value being checked
	*/
	public final static function expectArrayNotEmpty($arg, $argName = '', $customMsg = '')
	{
		if(!is_array($arg) || empty($arg))
			throw new Main\ArgumentException(self::formMessage('TASKS_ASSERT_ARRAY_NOT_EMPTY_EXPECTED', $argName, $customMsg));

		return $arg;
	}

	/**
	* Method checks if the given argument is a non-empty array of unique positive integers (or somehow can be casted to it)
	*
	* @param mixed[] $arg Argument to check.
	* @param string $argName Argument name to figure in a error message.
	* @param string $customMsg Custom message to be shown instead of a standard one.
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return integer[] checked and casted value
	*/
	public final static function expectArrayOfUniqueIntegerNotNull($arg, $argName = '', $customMsg = '')
	{
		if(!is_array($arg))
			throw new Main\ArgumentException(self::formMessage('TASKS_ASSERT_ARRAY_EXPECTED', $argName, $customMsg));

		$arg = array_unique(array_values($arg));

		foreach($arg as $k => $v)
		{
			$vInt = intval($v);
			if(((string) $v !== (string) $vInt) || $vInt == 0)
				throw new Main\ArgumentException(self::formMessage('TASKS_ASSERT_ARRAY_OF_INTEGER_NOT_NULL_EXPECTED', $argName, $customMsg));

			$arg[$k] = $vInt; // it can be casted to integer
		}

		return $arg;
	}

	/**
	* Method checks if the given argument is a non-empty array of unique non-zero-length strings (or somehow can be casted to it)
	*
	* @param mixed[] $arg Argument to check.
	* @param string $argName Argument name to figure in a error message.
	* @param string $customMsg Custom message to be shown instead of a standard one.
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return string[] checked and casted value
	*/
	public final static function expectArrayOfUniqueStringNotNull($arg, $argName = '', $customMsg = '')
	{
		if(!is_array($arg))
			throw new Main\ArgumentException(self::formMessage('TASKS_ASSERT_ARRAY_EXPECTED', $argName, $customMsg));

		$arg = array_unique(array_values($arg));

		foreach($arg as $k => $v)
		{
			$v = (string) $v;
			if(!strlen($v))
				throw new Main\ArgumentException(self::formMessage('TASKS_ASSERT_ARRAY_OF_STRING_NOT_NULL_EXPECTED', $argName, $customMsg));

			$arg[$k] = $v;
		}

		return $arg;
	}

	/**
	* Method checks if the given argument belongs to a set of elements
	*
	* @param mixed[] $arg Argument to check.
	* @param mixed[] $enum Enumeration to check argument belong to.
	* @param string $argName Argument name to figure in a error message.
	* @param string $customMsg Custom message to be shown instead of a standard one.
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return mixed[] checked and casted value
	*/
	public final static function expectEnumerationMember($arg, $enum = array(), $argName = '', $customMsg = '')
	{
		if(!strlen($arg))
			throw new Main\ArgumentException(Loc::getMessage('TASKS_ASSERT_EMPTY_ARGUMENT'));

		if(!is_array($enum) || empty($enum))
			throw new Main\ArgumentException(Loc::getMessage('TASKS_ASSERT_EMPTY_ENUMERATION'));

		// we cannot use in_array() here, kz we need for real data type
		foreach($enum as $variant)
		{
			if($variant == $arg)
				return $variant;
		}

		throw new Main\ArgumentException(self::formMessage('TASKS_ASSERT_ITEM_NOT_IN_ENUMERATION', $argName, $customMsg));
	}

	private final static function formMessage($msgCode, $argName = '', $customMsg = '')
	{
		if(strlen($customMsg))
		{
			return str_replace('#ARG_NAME#', $argName, $customMsg);
		}

		return Loc::getMessage($msgCode, array('#ARG_NAME#' => strlen($argName) ? ' "'.$argName.'" ' : ' '));
	}
}
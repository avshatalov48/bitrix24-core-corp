<?
/**
 * @access private
 * @internal
 */

namespace Bitrix\Tasks\Util;

final class Filter
{
	private $conditions = null;

	const EQUALITY_LIKE = 1;
	const EQUALITY_STRICT = 2;
	const EQUALITY_REGEXP = 3;

	public function __construct($conditions)
	{
		$this->conditions = static::parseConditions($conditions);
	}

	public function match($item)
	{
		if(!is_object($item) && !is_array($item))
		{
			return false; // filter can not be applied to the basic data type, only array and arrayaccess supported
		}

		foreach($this->conditions as $condition)
		{
			$field = $condition['F'];
			$equality = $condition['E'];

			$match = false; // item does not match the unknown condition
			if($equality == static::EQUALITY_STRICT || $equality == static::EQUALITY_LIKE)
			{
				$match = $item[$field] == $condition['V'];
			}
			elseif($equality == static::EQUALITY_REGEXP)
			{
				$match = preg_match($condition['V'], $item[$field]);
			}

			if($condition['I'])
			{
				$match = !$match;
			}

			return $match;
		}

		return false;
	}

	public static function isA($object)
	{
		return is_a($object, get_called_class());
	}

	private static function parseConditions($conditions)
	{
		$parsed = array();

		// todo: only one level supported currently
		// todo: use parseFilter here like this:
		/*
		\Bitrix\Tasks\Internals\DataBase\Helper\Common::parseFilter()
		*/

		if(is_array($conditions))
		{
			foreach($conditions as $c => $v)
			{
				$inverse = false;
				$equals = static::EQUALITY_LIKE;

				$c = trim((string) $c);

				// todo: make rich syntax here, currently only = and != supported
				if($c[0] == '!')
				{
					$inverse = true;
					$c = substr($c, 1);
				}
				if($c[0] == '=')
				{
					$equals = static::EQUALITY_STRICT;
					$c = substr($c, 1);
				}
				elseif($c[0] == '~')
				{
					$equals = static::EQUALITY_REGEXP;
					$c = substr($c, 1);
				}

				if($c != '')
				{
					$parsed[] = array('I' => $inverse, 'E' => $equals, 'F' => $c, 'V' => $v);
				}
			}
		}

		return $parsed;
	}
}
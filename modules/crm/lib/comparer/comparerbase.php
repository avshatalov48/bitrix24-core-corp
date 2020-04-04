<?php
namespace Bitrix\Crm\Comparer;

class ComparerBase
{
	public static function areFieldsEquals(array $left, array $right, $name)
	{
		if(!isset($left[$name]) && !isset($right[$name]))
		{
			return true;
		}
		return isset($left[$name]) && isset($right[$name]) && $left[$name] == $right[$name];
	}

	public function areEquals(array $a, array $b)
	{
		return false;
	}
}
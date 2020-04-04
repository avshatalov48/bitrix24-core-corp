<?php

namespace Bitrix\Tasks\Internals\Fields;

abstract class Common
{
	public static function getClass()
	{
		return get_called_class();
	}
}
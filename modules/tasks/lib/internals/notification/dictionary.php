<?php

namespace Bitrix\Tasks\Internals\Notification;

class Dictionary extends \Bitrix\Main\Type\Dictionary
{
	public function get($name, mixed $default = null)
	{
		$value = parent::get($name);
		return is_null($value) ? $default : $value;
	}
}
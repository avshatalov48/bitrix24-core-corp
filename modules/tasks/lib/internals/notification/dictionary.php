<?php

namespace Bitrix\Tasks\Internals\Notification;

class Dictionary extends \Bitrix\Main\Type\Dictionary
{
	public function get($name, mixed $default = null)
	{
		$value = parent::get($name);
		return is_null($value) ? $default : $value;
	}

	public function merge(string $name, array $values): void
	{
		$value = $this->get($name);
		if (!is_array($value))
		{
			$value = [$value];
		}

		$this->set($name, array_merge($value, $values));
	}
}
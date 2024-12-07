<?php

namespace Bitrix\Tasks\Flow\Attribute;

use Attribute;
use Bitrix\Tasks\Internals\Attribute\CheckInterface;

#[Attribute]
class EntitySelector implements CheckInterface
{
	public function check(mixed $value): bool
	{
		if (!is_array($value))
		{
			return false;
		}

		foreach ($value as $item)
		{
			if (!$this->checkItem($item))
			{
				return false;
			}
		}

		return true;
	}

	private function checkItem(mixed $value): bool
	{
		if (!is_array($value))
		{
			return false;
		}

		if (count($value) !== 2)
		{
			return false;
		}

		return true;
	}
}
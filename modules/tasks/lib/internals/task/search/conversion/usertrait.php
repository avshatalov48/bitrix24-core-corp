<?php

namespace Bitrix\Tasks\Internals\Task\Search\Conversion;

use Bitrix\Tasks\Util\User;

trait UserTrait
{
	public function getUserNames(): string
	{
		$value = $this->getFieldValue();
		$value = is_array($value) ? $value : [$value];

		return implode(' ', User::getUserName($value, null, null, true));
	}
}
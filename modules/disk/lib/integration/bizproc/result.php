<?php

namespace Bitrix\Disk\Integration\Bizproc;

class Result extends \Bitrix\Bizproc\Result
{
	public static function createFromErrorCode(string $code, $customData = null): static
	{
		return static::createError(Error::fromCode($code, $customData));
	}
}
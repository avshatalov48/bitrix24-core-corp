<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;

enum MemberEntityType: string
{
	case USER = 'USER';

	public function isUser(): bool
	{
		return $this === self::USER;
	}

	use ValuesTrait;
}

<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Trait\ValuesTrait;

enum MemberEntityType: string
{
	case USER = 'USER';

	use ValuesTrait;
}

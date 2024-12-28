<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Trait\ValuesTrait;

enum RoleChildAffectionType: int
{
	case NO_AFFECTION = 0;
	case AFFECTING = 1;

	use ValuesTrait;
}

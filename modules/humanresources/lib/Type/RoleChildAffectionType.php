<?php

namespace Bitrix\HumanResources\Type;

enum RoleChildAffectionType: int
{
	case NO_AFFECTION = 0;
	case AFFECTING = 1;

	use ValuesTrait;
}

<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Trait\ValuesTrait;

enum MemberSubordinateRelationType: int
{
	case RELATION_ITSELF = 0;
	case RELATION_HIGHER = 1;
	case RELATION_EQUAL = 2;
	case RELATION_LOWER = 3;
	case RELATION_OTHER = 4;
	case RELATION_OTHER_STRUCTURE = 5;

	use ValuesTrait;
}

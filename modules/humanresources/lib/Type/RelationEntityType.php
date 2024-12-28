<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Trait\ValuesTrait;

enum RelationEntityType: string
{
	case CHAT = 'CHAT';

	use ValuesTrait;
}

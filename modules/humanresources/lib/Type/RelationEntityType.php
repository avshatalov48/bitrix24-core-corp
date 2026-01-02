<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;

enum RelationEntityType: string
{
	case CHAT = 'CHAT';
	case COLLAB = 'COLLAB';

	use ValuesTrait;
}

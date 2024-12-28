<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Trait\ValuesTrait;

enum RoleEntityType: string
{
	case NODE = 'NODE';
	case MEMBER = 'MEMBER';

	use ValuesTrait;
}

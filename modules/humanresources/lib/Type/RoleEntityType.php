<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;

enum RoleEntityType: string
{
	case NODE = 'NODE';
	case MEMBER = 'MEMBER';

	use ValuesTrait;
}

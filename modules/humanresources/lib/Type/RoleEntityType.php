<?php

namespace Bitrix\HumanResources\Type;

enum RoleEntityType: string
{
	case NODE = 'NODE';
	case MEMBER = 'MEMBER';

	use ValuesTrait;
}

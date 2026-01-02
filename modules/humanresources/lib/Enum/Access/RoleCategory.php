<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Enum\Access;

enum RoleCategory: string
{
	case Team = 'TEAM';
	case Department = 'DEPARTMENT';
}
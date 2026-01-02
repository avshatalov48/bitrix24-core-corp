<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;

enum NodeEntityType: string
{
	case DEPARTMENT = 'DEPARTMENT';
	case TEAM = 'TEAM';

	public function isDepartment(): bool
	{
		return $this === self::DEPARTMENT;
	}

	public function isTeam(): bool
	{
		return $this === self::TEAM;
	}

	use ValuesTrait;
}

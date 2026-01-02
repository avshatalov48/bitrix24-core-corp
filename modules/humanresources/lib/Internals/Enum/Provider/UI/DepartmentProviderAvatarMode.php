<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Internals\Enum\Provider\UI;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;

enum DepartmentProviderAvatarMode: string
{
	case None = 'none';
	case Item = 'item';
	case Node = 'node';
	case Both = 'both';

	use ValuesTrait;
}

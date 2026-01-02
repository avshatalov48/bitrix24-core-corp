<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Internals\Enum\Provider\UI;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;

enum DepartmentProviderTagStyleMode: string
{
	case Default = 'default';
	case None = 'none';

	use ValuesTrait;
}


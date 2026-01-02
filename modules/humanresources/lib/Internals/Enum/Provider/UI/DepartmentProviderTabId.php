<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Internals\Enum\Provider\UI;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;

enum DepartmentProviderTabId: string
{
	case Departments = 'structure-departments-tab';
	case Teams = 'structure-teams-tab';
	case Recent = 'recent';

	use ValuesTrait;
}

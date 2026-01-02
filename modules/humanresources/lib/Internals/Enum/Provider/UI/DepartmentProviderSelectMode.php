<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Internals\Enum\Provider\UI;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;

enum DepartmentProviderSelectMode: string
{
	case DepartmentsOnly = 'departmentsOnly';
	case UsersOnly = 'usersOnly';
	case UsersAndDepartments = 'usersAndDepartments';

	use ValuesTrait;
}

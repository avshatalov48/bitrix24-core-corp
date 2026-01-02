<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;

/**
 * Authority Settings Values
 *
 * Enum values meant to be equal to `name` field of `b_hr_structure_role` table
 */
enum NodeSettingsAuthorityType: string
{
	case DepartmentHead = 'HEAD';
	case DepartmentDeputy = 'DEPUTY_HEAD';
	case DepartmentEmployee = 'EMPLOYEE';
	case TeamHead = 'TEAM_HEAD';
	case TeamDeputy = 'TEAM_DEPUTY';
	case TeamEmployee = 'TEAM_EMPLOYEE';

	use ValuesTrait;
}

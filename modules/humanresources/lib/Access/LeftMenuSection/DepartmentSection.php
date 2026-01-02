<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Access\LeftMenuSection;

use Bitrix\HumanResources\Enum\Access\RoleCategory;
use Bitrix\Main\Localization\Loc;

final class DepartmentSection extends MenuSectionBaseClass
{
	public function getMenuId(): string
	{
		return 'hr-department-menu-section';
	}

	public function getTitle(): string
	{
		return Loc::getMessage('HUMANRESOURCES_ACCESS_LEFT_MENU_DEPARTMENT_SECTION_TITLE') ?? '';
	}

	public function getCategory(): RoleCategory
	{
		return RoleCategory::Department;
	}
}

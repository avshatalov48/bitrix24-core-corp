<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Access\LeftMenuSection;

use Bitrix\HumanResources\Enum\Access\RoleCategory;
use Bitrix\Main\Localization\Loc;

final class TeamSection extends MenuSectionBaseClass
{
	public function getMenuId(): string
	{
		return 'hr-team-menu-section';
	}

	public function getTitle(): string
	{
		return Loc::getMessage('HUMANRESOURCES_ACCESS_LEFT_MENU_TEAM_SECTION_TITLE') ?? '';
	}

	public function getCategory(): RoleCategory
	{
		return RoleCategory::Team;
	}
}
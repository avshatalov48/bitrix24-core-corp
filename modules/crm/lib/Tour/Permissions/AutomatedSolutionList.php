<?php

namespace Bitrix\Crm\Tour\Permissions;

use Bitrix\Crm\Tour\AbstractPermissions;
use Bitrix\Main\Localization\Loc;

final class AutomatedSolutionList extends AbstractPermissions
{
	protected const OPTION_NAME = 'automated-solution-list-permissions-tour';

	protected function hasPermissions(): bool
	{
		return $this->userPermissions->isAutomatedSolutionsAdmin();
	}

	protected function title(): string
	{
		return Loc::getMessage('CRM_TOUR_PERMISSIONS_AUTOMATED_SOLUTION_LIST_TITLE');
	}

	protected function text(): string
	{
		return Loc::getMessage('CRM_TOUR_PERMISSIONS_AUTOMATED_SOLUTION_LIST_TEXT');
	}
}

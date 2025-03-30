<?php

namespace Bitrix\Crm\Tour\Permissions;

use Bitrix\Crm\Tour\AbstractPermissions;
use Bitrix\Main\Localization\Loc;

final class Widget extends AbstractPermissions
{
	protected const OPTION_NAME = 'widget-permissions-tour';

	protected function hasPermissions(): bool
	{
		return $this->userPermissions->canWriteButtonConfig();
	}

	protected function title(): string
	{
		return Loc::getMessage('CRM_TOUR_PERMISSIONS_WIDGET_TITLE');
	}

	protected function text(): string
	{
		return Loc::getMessage('CRM_TOUR_PERMISSIONS_WIDGET_TEXT');
	}
}

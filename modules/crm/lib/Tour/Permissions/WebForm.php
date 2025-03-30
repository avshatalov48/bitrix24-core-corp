<?php

namespace Bitrix\Crm\Tour\Permissions;

use Bitrix\Crm\Tour\AbstractPermissions;
use Bitrix\Main\Localization\Loc;

final class WebForm extends AbstractPermissions
{
	protected const OPTION_NAME = 'webform-permissions-tour';

	protected function hasPermissions(): bool
	{
		return $this->userPermissions->canWriteWebFormConfig();
	}

	protected function title(): string
	{
		return Loc::getMessage('CRM_TOUR_PERMISSIONS_WEBFORM_TITLE');
	}

	protected function text(): string
	{
		return Loc::getMessage('CRM_TOUR_PERMISSIONS_WEBFORM_TEXT');
	}
}

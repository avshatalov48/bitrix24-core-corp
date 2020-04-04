<?php
namespace Bitrix\Crm\Activity;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class AutocompleteRule
{
	const NONE = 0;
	const AUTOMATION_ON_STATUS_CHANGED = 1;

	public static function getDescriptions()
	{
		return array(
			static::NONE                         => Loc::getMessage('CRM_ACTIVITY_ACR_NONE'),
			static::AUTOMATION_ON_STATUS_CHANGED => Loc::getMessage('CRM_ACTIVITY_ACR_AUTOMATION_STATUS_CHANGED'),
		);
	}
}
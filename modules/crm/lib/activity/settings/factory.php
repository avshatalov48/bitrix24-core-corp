<?php

namespace Bitrix\Crm\Activity\Settings;

use Bitrix\Crm\Activity\Settings\Section\Calendar;

final class Factory
{
	public static function getInstance(
		string $name,
		array $data = [],
		array $activityData = []
	): SettingsInterface
	{
		if ($name === Calendar::TYPE_NAME)
		{
			return new Calendar($data, $activityData);
		}

		throw new UnknownSettingsSectionException('Activity settings class: ' . $name . ' is not known');
	}
}

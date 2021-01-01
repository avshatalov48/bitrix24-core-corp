<?php
namespace Bitrix\Timeman\Update;

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Monitor\Group\Group;

class TimemanMonitor
{
	public static function addPresetGroupsAgent(): string
	{
		if (!Group::isTableEmpty())
		{
			return '';
		}

		Group::add(
			Loc::getMessage('TIMEMAN_MONITOR_DEFAULT_GROUP_WORK'),
			Group::CODE_WORKING,
			'#00bdb6',
			false,
			true
		);

		Group::add(
			Loc::getMessage('TIMEMAN_MONITOR_DEFAULT_GROUP_NOT_WORK'),
			Group::CODE_NOT_WORKING,
			'#fe5b54',
			false,
			true
		);

		Group::add(
			Loc::getMessage('TIMEMAN_MONITOR_DEFAULT_GROUP_OTHERS'),
			Group::CODE_OTHER,
			'#a1a6ac',
			false,
			true
		);

		return '';
	}
}
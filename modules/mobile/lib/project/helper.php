<?php

namespace Bitrix\Mobile\Project;

use Bitrix\Main\Loader;

class Helper
{
	public static function getProjectNewsPathTemplate(array $params = []): string
	{
		$siteDir = ($params['siteDir'] ?: SITE_DIR);

		return $siteDir . 'mobile/log/?group_id=#group_id#';
	}

	public static function getProjectCalendarWebPathTemplate(array $params = []): string
	{
		$siteDir = ($params['siteDir'] ?: SITE_DIR);
		$siteId = ($params['siteId'] ?? SITE_ID);

		$result = (
			Loader::includeModule('socialnetwork')
				? \Bitrix\Socialnetwork\Helper\Path::get('group_calendar_path_template', $siteId)
				: ''
		);

		if ($result === '')
		{
			$result = $siteDir . 'workgroups/group/#group_id#/calendar/';
		}

		return $result;
	}

	public static function getMobileFeatures(): array
	{
		return [
			'tasks',
			'blog',
			'files',
			'calendar',
		];
	}

	public static function getMobileMandatoryFeatures(): array
	{
		return [
			'blog',
		];
	}
}

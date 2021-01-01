<?php
namespace Bitrix\Timeman\Monitor\History;

use Bitrix\Timeman\Model\Monitor\MonitorAppTable;
use Bitrix\Timeman\Monitor\Group\DepartmentView;
use Bitrix\Timeman\Monitor\Group\Group;
use Bitrix\Timeman\Monitor\Group\GroupAccess;

class App
{
	public static function add($apps): void
	{
		$appCodes = [];
		foreach ($apps as $app)
		{
			$appCodes[] = $app['code'];
		}

		$existingSites = MonitorAppTable::getList([
			'select' => ['CODE', 'ID'],
			'filter' => ['@CODE' => $appCodes]
		])->fetchAll();

		$existingCodes = [];
		$existingIds = [];
		foreach ($existingSites as $app)
		{
			$existingCodes[] = $app['CODE'];
			$existingIds[] = $app['ID'];
		}

		DepartmentView::addAppForCurrentUserDepartments($existingIds);

		$newCodes = self::findNewCodes($appCodes, $existingCodes);

		$newApps = [];
		foreach ($apps as $app)
		{
			if ($app['name'] && in_array($app['code'], $newCodes, true))
			{
				$newApps[] = $app;
			}
		}

		$newAppIds = [];
		foreach ($newApps as $newApp)
		{
			$appAddResult = MonitorAppTable::add([
				'CODE' => $newApp['code'],
				'NAME' => $newApp['name']
			]);

			if ($appAddResult->isSuccess())
			{
				$newAppIds[] = $appAddResult->getId();
			}
		}

		if ($newAppIds)
		{
			DepartmentView::addAppForCurrentUserDepartments($newAppIds);
		}
	}

	private static function findNewCodes($neededCodes, $existingCodes): array
	{
		$result = [];
		foreach ($neededCodes as $code)
		{
			if (!in_array($code, $existingCodes, true))
			{
				$result[] = $code;
			}
		}

		return $result;
	}
}
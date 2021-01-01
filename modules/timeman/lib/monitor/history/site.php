<?php
namespace Bitrix\Timeman\Monitor\History;

use Bitrix\Timeman\Model\Monitor\MonitorSiteTable;
use Bitrix\Timeman\Monitor\Group\DepartmentView;
use Bitrix\Timeman\Monitor\Group\Group;
use Bitrix\Timeman\Monitor\Group\GroupAccess;

class Site
{
	public static function add($sites): void
	{
		$siteCodes = [];
		foreach ($sites as $site)
		{
			$siteCodes[] = $site['code'];
		}

		$existingSites = MonitorSiteTable::getList([
			'select' => ['CODE', 'ID'],
			'filter' => ['@CODE' => $siteCodes]
		])->fetchAll();

		$existingCodes = [];
		$existingIds = [];
		foreach ($existingSites as $site)
		{
			$existingCodes[] = $site['CODE'];
			$existingIds[] = $site['ID'];
		}

		DepartmentView::addSiteForCurrentUserDepartments($existingIds);

		$newCodes = self::findNewCodes($siteCodes, $existingCodes);

		$newSites = [];
		foreach ($sites as $site)
		{
			if ($site['host'] && in_array($site['code'], $newCodes, true))
			{
				$newSites[] = $site;
			}
		}

		$newSiteIds = [];
		foreach ($newSites as $newSite)
		{
			$siteAddResult = MonitorSiteTable::add([
				'CODE' => $newSite['code'],
				'HOST' => $newSite['host']
			]);

			if ($siteAddResult->isSuccess())
			{
				$newSiteIds[] = $siteAddResult->getId();
			}
		}

		if ($newSiteIds)
		{
			DepartmentView::addSiteForCurrentUserDepartments($newSiteIds);
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
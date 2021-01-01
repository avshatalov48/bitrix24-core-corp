<?php
namespace Bitrix\Timeman\Monitor\History;

use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Type\Date;
use Bitrix\Timeman\Model\Monitor\MonitorSiteTable;
use Bitrix\Timeman\Model\Monitor\MonitorUserPageTable;

class UserPage
{
	public static function add($history): void
	{
		global $USER;
		$userId = $USER->GetID();

		foreach ($history as $day => $entries)
		{
			$date = new Date($day, 'Y-m-j');
			foreach ($entries as $entry)
			{
				if (!$entry['pageCode'])
				{
					continue;
				}

				MonitorUserPageTable::merge([
					'DATE_LOG' => $date,
					'USER_ID' => $userId,
					'DESKTOP_CODE' => $entry['desktopCode'],
					'CODE' => $entry['code'],
					'PAGE_CODE' => $entry['pageCode'],
					'SITE_CODE' => $entry['siteCode'],
					'SITE_URL' => $entry['siteUrl'],
					'SITE_TITLE' => $entry['siteTitle'],
					'TIME_SPEND' => $entry['time']
				]);
			}
		}
	}

	public static function getForUserOnDate(int $userId, int $siteId, Date $date): array
	{
		$query = new Query(MonitorUserPageTable::getEntity());

		$query->setSelect([
			'SITE_HOST' => 'site.HOST',
			'SITE_URL',
			'SITE_TITLE',
			'SITE_ID' => 'site.ID',
			'SITE_NAME' => 'site.NAME',
			'TIME_SPEND',
		]);

		$query->registerRuntimeField(new ReferenceField(
			'site',
			MonitorSiteTable::class,
			Join::on('this.SITE_CODE', 'ref.CODE')
		));

		$query->addFilter('=USER_ID', $userId);
		$query->addFilter('=site.ID', $siteId);
		$query->addFilter('=DATE_LOG', $date);

		$result = $query->exec()->fetchAll();
		foreach ($result as $key => $entity)
		{
			if (!$entity['SITE_ID'])
			{
				unset($result[$key]);
			}
		}

		return $result;
	}
}
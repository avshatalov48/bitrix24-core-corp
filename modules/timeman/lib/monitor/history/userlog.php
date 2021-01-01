<?php
namespace Bitrix\Timeman\Monitor\History;

use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\Date;
use Bitrix\Timeman\Model\Monitor\MonitorAppTable;
use Bitrix\Timeman\Model\Monitor\MonitorSiteTable;
use Bitrix\Timeman\Model\Monitor\MonitorUserLogTable;

class UserLog
{
	public static function add($history): void
	{
		global $USER;
		$userId = $USER->GetID();

		$fullTimeSpend = [];
		foreach ($history as $day => $entries)
		{
			foreach ($entries as $key => $entry)
			{
				$fullTimeSpend[$day][$entry['code']]['TIME_SPEND'] += $entry['time'];
			}
		}

		foreach ($history as $day => $entries)
		{
			$date = new Date($day, 'Y-m-j');
			foreach ($entries as $entry)
			{
				MonitorUserLogTable::merge([
					'DATE_LOG' => $date,
					'USER_ID' => $userId,
					'DESKTOP_CODE' => $entry['desktopCode'],
					'CODE' => $entry['code'],
					'APP_CODE' => $entry['appCode'],
					'SITE_CODE' => $entry['siteCode'],
					'TIME_SPEND' => $fullTimeSpend[$day][$entry['code']]['TIME_SPEND']
				]);
			}
		}
	}

	public static function getForPeriod(int $userId, Date $dateStart, Date $dateFinish): array
	{
		$query = new Query(MonitorUserLogTable::getEntity());

		$query->setSelect([
			'APP_ID' => 'app.ID',
			'APP_NAME' => 'app.NAME',
			'SITE_HOST' => 'site.HOST',
			'SITE_ID' => 'site.ID',
			'SITE_NAME' => 'site.NAME',
			'TIME',
		]);

		$query->registerRuntimeField(new ReferenceField(
			'site',
			MonitorSiteTable::class,
			Join::on('this.SITE_CODE', 'ref.CODE')
		));

		$query->registerRuntimeField(new ReferenceField(
			'app',
			MonitorAppTable::class,
			Join::on('this.APP_CODE', 'ref.CODE')
		));

		$query->registerRuntimeField(new ExpressionField(
			'TIME',
			'sum(%s)',
			['TIME_SPEND']
		));

		$query->addFilter('=USER_ID', $userId);
		$query->whereBetween('DATE_LOG', $dateStart, $dateFinish);
		$query->addOrder('TIME', 'DESC');

		$result = $query->exec()->fetchAll();
		foreach ($result as $key => $entity)
		{
			if (!$entity['APP_ID'] && !$entity['SITE_ID'])
			{
				unset($result[$key]);
			}
		}

		return $result;
	}
}
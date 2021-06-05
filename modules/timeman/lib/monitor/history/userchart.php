<?php
namespace Bitrix\Timeman\Monitor\History;

use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Timeman\Model\Monitor\MonitorUserChartTable;
use Bitrix\Timeman\Monitor\Utils\User;

class UserChart
{
	public static function record($history): void
	{
		$date = new Date($history['dateLog'], 'Y-m-j');

		foreach ($history['chartPackage'] as $index => $entry)
		{
			MonitorUserChartTable::add([
				'DATE_LOG' => $date,
				'USER_ID' => User::getCurrentUserId(),
				'DESKTOP_CODE' => $history['desktopCode'],
				'GROUP_TYPE' => $entry['type'],
				'TIME_START' => new DateTime($entry['start'], \DateTime::ATOM),
				'TIME_FINISH' => new DateTime($entry['finish'], \DateTime::ATOM),
			]);
		}
	}

	public static function getOnDate(int $userId, Date $date): array
	{
		$query = MonitorUserChartTable::query();

		$query->setSelect([
			'TYPE' => 'GROUP_TYPE',
			'START' => 'TIME_START',
			'FINISH' => 'TIME_FINISH',
			'DESKTOP_CODE',
		]);

		$query->addFilter('=USER_ID', $userId);
		$query->addFilter('=DATE_LOG', $date);

		$rawChartData = $query->exec()->fetchAll();

		$chartDataByDesktop = [];

		$chartDataByDesktop['INFO'] = [
			'USER_ID' => $userId
		];

		foreach ($rawChartData as $chartData)
		{
			$chartDataByDesktop[$chartData['DESKTOP_CODE']]['CHART_DATA'][] = [
				'TYPE' => $chartData['TYPE'],
				'START' => $chartData['START'],
				'FINISH' => $chartData['FINISH'],
			];
		}

		return $chartDataByDesktop;
	}
}
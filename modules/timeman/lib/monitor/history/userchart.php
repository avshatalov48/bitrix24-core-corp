<?php
namespace Bitrix\Timeman\Monitor\History;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
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
				'TIME_START' => new DateTime($entry['start'], \DateTimeInterface::RFC3339),
				'TIME_FINISH' => new DateTime($entry['finish'], \DateTimeInterface::RFC3339),
			]);
		}
	}

	public static function remove(int $userId, string $dateLog, string $desktopCode): Result
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$dateLog = $sqlHelper->forSql($dateLog);
		$desktopCode = $sqlHelper->forSql($desktopCode);

		$deleteUserChartQuery = "
			DELETE FROM b_timeman_monitor_user_chart 
			WHERE DATE_LOG = '{$dateLog}' 
			  and USER_ID = {$userId} 
			  and DESKTOP_CODE = '{$desktopCode}'
		";

		return $connection->query($deleteUserChartQuery);
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

		return $query->exec()->fetchAll();
	}

	public static function getReportOnDate(int $userId, Date $date): array
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
			$chartDataByDesktop['DATA'][$chartData['DESKTOP_CODE']]['CHART_DATA'][] = [
				'type' => $chartData['TYPE'],
				'start' => $chartData['START']->format('Y-m-d' . '\T' . 'H:i:s' . '\.\0\0\Z'),
				'finish' => $chartData['FINISH']->format('Y-m-d' . '\T' . 'H:i:s' . '\.\0\0\Z'),
			];
		}

		return $chartDataByDesktop;
	}
}
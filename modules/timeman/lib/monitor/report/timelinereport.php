<?php
namespace Bitrix\Timeman\Monitor\Report;

use Bitrix\Main\ORM\Query\Query;
use Bitrix\Timeman\Model\Monitor\MonitorUserChartTable;

class TimelineReport extends BaseReport
{
	protected function createQuery(): Query
	{
		$query = MonitorUserChartTable::query();

		$query->setSelect([
			'USER_ID',
			'TYPE' => 'GROUP_TYPE',
			'START' => 'TIME_START',
			'FINISH' => 'TIME_FINISH',
			'DATE_LOG',
			'DESKTOP_CODE',
		]);

		$query->addOrder('USER_ID');
		$query->addOrder('DATE_LOG', 'DESC');
		$query->addOrder('START');

		if ($this->getFilter() !== null)
		{
			$query->setFilter($this->getFilter());
		}

		return $query;
	}

	public function getData(): array
	{
		$reportData = parent::getData();

		if (!$reportData)
		{
			return [];
		}

		$chartDataByUser = [];

		foreach ($reportData as $interval)
		{
			$userId = $interval['USER_ID'];
			$dateLog = $interval['DATE_LOG']->format('Y-m-d');
			$desktopCode = $interval['DESKTOP_CODE'];

			$chartDataByUser[$userId][$dateLog][$desktopCode][] = [
				'type' => $interval['TYPE'],
				'start' => $interval['START']->format('Y-m-d' . '\T' . 'H:i:s' . '\.\0\0\Z'),
				'finish' => $interval['FINISH']->format('Y-m-d' . '\T' . 'H:i:s' . '\.\0\0\Z'),
			];
		}

		return $chartDataByUser;
	}

	public function getTotalCount(): int
	{
		return $this->getTotalCountByColumnName('DATE_LOG');
	}
}

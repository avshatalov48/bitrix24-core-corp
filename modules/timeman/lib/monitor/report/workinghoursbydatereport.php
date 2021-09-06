<?php
namespace Bitrix\Timeman\Monitor\Report;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Timeman\Model\Monitor\MonitorUserLogTable;
use Bitrix\Timeman\Monitor\Utils\Time;

class WorkingHoursByDateReport extends BaseReport
{
	protected function createQuery(): Query
	{
		$query = MonitorUserLogTable::query();

		$query->setSelect([
			'DATE_LOG',
			'USER_ID',
			'WORKING_HOURS',
			'DESKTOP_CODE',
		]);

		$query->registerRuntimeField(new ExpressionField(
			'WORKING_HOURS',
			'sum(%s)',
			'TIME_SPEND'
		));

		$query->addGroup('DATE_LOG');
		$query->addGroup('USER_ID');
		$query->addGroup('DESKTOP_CODE');

		if ($this->getFilter() !== null)
		{
			$query->setFilter($this->getFilter());
		}

		if ($this->getOrder() !== null)
		{
			$query->setOrder($this->getOrder());
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

		$workingHoursByUsers = [];

		foreach ($reportData as $interval)
		{
			$userId = $interval['USER_ID'];
			$dateLog = $interval['DATE_LOG']->format('Y-m-d');
			$desktopCode = $interval['DESKTOP_CODE'];

			$workingHoursByUsers[$userId][$dateLog][$desktopCode] = [
				'value' => $interval['WORKING_HOURS'],
				'formatted' => Time::format($interval['WORKING_HOURS']),
			];
		}

		return $workingHoursByUsers;
	}

	public function getTotalCount(): int
	{
		return $this->getTotalCountByColumnName('DATE_LOG');
	}
}

<?php
namespace Bitrix\Timeman\Monitor\Report;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Timeman\Model\Monitor\MonitorUserLogTable;
use Bitrix\Timeman\Monitor\Utils\User;

class WorkingHoursReport extends BaseReport
{
	protected function createQuery(): Query
	{
		$query = MonitorUserLogTable::query();

		$query->setSelect([
			'USER_ID',
			'WORKING_HOURS'
		]);

		$query->registerRuntimeField(new ExpressionField(
			'WORKING_HOURS',
			'round(sum(%s) / 60 / 60, 0)',
			'TIME_SPEND'
		));

		if ($this->getFilter() !== null)
		{
			$query->setFilter($this->getFilter());
		}

		if ($this->getOrder() !== null)
		{
			$query->setOrder($this->getOrder());
		}

		if ($this->getLimit() !== null)
		{
			$query->setLimit($this->getLimit());
		}

		if ($this->getOffset() !== null)
		{
			$query->setOffset($this->getOffset());
		}

		return $query;
	}

	public function getData(): array
	{
		$reportData = parent::getData();

		if ($reportData)
		{
			User::preloadUserInfo(array_column($reportData, 'EMPLOYEE_ID'));

			foreach ($reportData as $index => $row)
			{
				$user = User::getUserInfo($row['USER_ID']);
				$reportData[$index]['USER_NAME'] = $user['name'];
				$reportData[$index]['USER_ICON'] = $user['icon'];
				$reportData[$index]['USER_LINK'] = $user['link'];
			}
		}

		return $reportData;
	}

	public function getTotalCount(): int
	{
		return $this->getTotalCountByColumnName('USER_ID');
	}
}

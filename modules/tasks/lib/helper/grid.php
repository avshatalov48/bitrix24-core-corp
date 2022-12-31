<?php

namespace Bitrix\Tasks\Helper;

use Bitrix\Main\Grid as MainGrid;

class Grid extends Common
{
	protected static $instance = null;

	/**
	 * @return array
	 */
	public function getVisibleColumns()
	{
		$columns = $this->getOptions()->GetVisibleColumns();

		if (empty($columns))
		{
			$columns = $this->getDefaultVisibleColumns();
		}

		return $columns;
	}

	/**
	 * @return MainGrid\Options
	 */
	public function getOptions()
	{
		static $instance = null;

		if (!$instance)
		{
			return new MainGrid\Options($this->getId());
		}

		return $instance;
	}

	/**
	 * @return array
	 */
	private function getDefaultVisibleColumns()
	{
		$defaultColumns = [
			'TITLE',
			'ACTIVITY_DATE',
			'DEADLINE',
			'ORIGINATOR_NAME',
			'RESPONSIBLE_NAME',
			'GROUP_NAME',
			'TAG',
		];

		/*break;
}
*/

		return $defaultColumns;
	}

	public function getAllColumns(): array
	{
		return [
			'ID',
			'TITLE',
			'DESCRIPTION',
			'ACTIVITY_DATE',
			'DEADLINE',
			'ORIGINATOR_NAME',
			'RESPONSIBLE_NAME',
			'A', //ACCOMPLICE
			'U', //AUDITOR
			'STATUS',
			'GROUP_NAME',
			'CREATED_DATE',
			'DATE_START',
			'CHANGED_DATE',
			'CLOSED_DATE',
			'TIME_ESTIMATE',
			'ALLOW_TIME_TRACKING',
			'MARK',
			'ALLOW_CHANGE_DEADLINE',
			'TIME_SPENT_IN_LOGS',
			'FLAG_COMPLETE',
			'TAG',
			'UF_CRM_TASK_LEAD',
			'UF_CRM_TASK_CONTACT',
			'UF_CRM_TASK_COMPANY',
			'UF_CRM_TASK_DEAL',
			'UF_CRM_TASK',

			'PARENT_ID',
			'PARENT_TITLE',
		];
	}
}
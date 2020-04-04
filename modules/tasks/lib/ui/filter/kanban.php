<?php

namespace Bitrix\Tasks\Ui\Filter;

class Kanban extends Task
{
	protected static $filterId = null;
	protected static $filterSuffix = '_KANBAN';

	/**
	 * Get available fields in filter.
	 * @return array
	 */
	protected function getAvailableFields()
	{
		return array('ID', 'TITLE', 'STATUS', 'PROBLEM', 'MARK', 'ALLOW_TIME_TRACKING',
					'DEADLINE', 'CREATED_DATE', 'CLOSED_DATE', 'DATE_START', 'START_DATE_PLAN',
					'END_DATE_PLAN', 'RESPONSIBLE_ID', 'CREATED_BY', 'ACCOMPLICE', 'AUDITOR');
	}
}
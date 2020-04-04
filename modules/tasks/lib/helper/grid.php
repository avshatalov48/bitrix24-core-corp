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
		/*
		$stateInstance = static::getListStateInstance();
		$roleId = $stateInstance->getUserRole();
		$section = $stateInstance->getSection();
		$typeFilter = \CTaskListState::VIEW_SECTION_ADVANCED_FILTER == $section ? 'ADVANCED' : 'MAIN';

		if($typeFilter == 'ADVANCED')
		{
			$roleId = 'default';
		}

		switch ($roleId)
		{
			case \CTaskListState::VIEW_ROLE_ACCOMPLICE:
			case \CTaskListState::VIEW_ROLE_RESPONSIBLE:
				$defaultColumns = array(
					'TITLE',
					'DEADLINE',
					'CREATED_BY',
//					'ORIGINATOR_NAME',
				);
				break;
			case \CTaskListState::VIEW_ROLE_ORIGINATOR:
				$defaultColumns = array(
					'TITLE',
					'DEADLINE',
					'RESPONSIBLE_ID',
//					'RESPONSIBLE_NAME'
				);
				break;
			case \CTaskListState::VIEW_ROLE_AUDITOR:
				$defaultColumns = array(
					'TITLE',
					'DEADLINE',
					'CREATED_BY',
//					'ORIGINATOR_NAME',
					'RESPONSIBLE_ID',
//					'RESPONSIBLE_NAME'
				);
				break;
			default:*/
		$defaultColumns = array(
			'TITLE',
			'DEADLINE',
			'CREATED_BY',
			'ORIGINATOR_NAME',
			'RESPONSIBLE_ID',
			'RESPONSIBLE_NAME'
		);

		/*break;
}
*/

		return $defaultColumns;
	}
}
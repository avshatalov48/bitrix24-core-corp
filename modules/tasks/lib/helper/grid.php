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
}
<?php

namespace Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service;

use Bitrix\Tasks\Internals\Task\LabelTable;

class TagService
{
	/**
	 * Fill data-array with tags.
	 * @param array $items Task items.
	 * @return array
	 */
	public function getTags(array $items): array
	{
		if (empty($items))
		{
			return $items;
		}

		$res = LabelTable::getList([
			'select' => [
				'TASK_ID' => 'TASKS.ID',
				'NAME'
			],
			'filter' => [
				'TASK_ID' => array_keys($items)
			],
		]);
		while ($row = $res->fetch())
		{
			$tags =& $items[$row['TASK_ID']]['data']['tags'];
			$tags[] = $row['NAME'];
		}

		return $items;
	}
}
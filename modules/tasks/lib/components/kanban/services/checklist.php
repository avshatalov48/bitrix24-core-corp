<?php

namespace Bitrix\Tasks\Components\Kanban\Services;

use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Tasks\Internals\Task\CheckListTable;
use Bitrix\Tasks\Internals\Task\CheckListTreeTable;

class CheckList
{
	/**
	 * Fill data-array with checklist.
	 * @param array $items Task items.
	 * @return array
	 */
	public function getCheckList(array $items): array
	{
		if (empty($items))
		{
			return $items;
		}

		$query = new Query(CheckListTable::getEntity());
		$query->setSelect(['TASK_ID', 'IS_COMPLETE', new ExpressionField('CNT', 'COUNT(TASK_ID)')]);
		$query->setFilter(['TASK_ID' => array_keys($items),]);
		$query->setGroup(['TASK_ID', 'IS_COMPLETE']);
		$query->registerRuntimeField('', new ReferenceField(
			'IT',
			CheckListTreeTable::class,
			Join::on('this.ID', 'ref.CHILD_ID')->where('ref.LEVEL', 1),
			['join_type' => 'INNER']
		));

		$res = $query->exec();
		while ($row = $res->fetch())
		{
			$checkList =& $items[$row['TASK_ID']]['data']['check_list'];
			$checkList[$row['IS_COMPLETE'] == 'Y' ? 'complete' : 'work'] = $row['CNT'];
		}

		return $items;
	}
}
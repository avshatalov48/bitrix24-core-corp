<?php

namespace Bitrix\Tasks\Components\Kanban\Services;


use Bitrix\Tasks\Internals\Task\LogTable;
use Bitrix\Tasks\Internals\Task\ViewedTable;

class Logs
{
	const USER_WEBDAV_CODE = 'UF_TASK_WEBDAV_FILES';

	private int $userId;

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	/**
	 * Fill data-array with new log-data.
	 * @param array $items Task items.
	 * @return array
	 */
	public function getNewLog(array $items): array
	{
		if (empty($items))
		{
			return $items;
		}
		// first get last viewed dates
		$res = ViewedTable::getList([
			'filter' => [
				'USER_ID' => $this->userId,
				'TASK_ID' => array_keys($items)
			]
		]);
		while ($row = $res->fetch())
		{
			$items[$row['TASK_ID']]['data']['date_view'] = $row['VIEWED_DATE'];
		}
		// then get new log after view
		$filterLog = array(
			'LOGIC' => 'OR'
		);
		foreach ($items as $id => &$item)
		{
			if ($item['data']['date_view'])
			{
				$filterLog[] = array(
					'>CREATED_DATE' => $item['data']['date_view'],
					'TASK_ID' => $id
				);
			}
			$item['data']['date_view'] = $item['data']['date_view'] ? $item['data']['date_view']->getTimestamp() : 0;
		}
		unset($item);
		$res = LogTable::getList([
			'select' => [
				'TASK_ID', 'FIELD', 'FROM_VALUE', 'TO_VALUE'
			],
			'filter' => [
				'!USER_ID' => $this->userId,
				'=FIELD' => [
					'COMMENT',
					self::USER_WEBDAV_CODE,
					'CHECKLIST_ITEM_CREATE'
				],
				$filterLog
			]
		]);
		while ($row = $res->fetch())
		{
			$log =& $items[$row['TASK_ID']]['data']['log'];

			// wee need only files and comments
			if ($row['FIELD'] == 'COMMENT')
			{
				$log['comment']++;
			}
			elseif ($row['FIELD'] == self::USER_WEBDAV_CODE)
			{
				$row['FROM_VALUE'] = $row['FROM_VALUE'] == '' ? 0 : count(explode(',', $row['FROM_VALUE']));
				$row['TO_VALUE'] = $row['TO_VALUE'] == '' ? 0 : count(explode(',', $row['TO_VALUE']));
				if ($row['TO_VALUE'] > $row['FROM_VALUE'])
				{
					$log['file'] += ($row['TO_VALUE'] - $row['FROM_VALUE']);
				}
			}
			elseif ($row['FIELD'] == 'CHECKLIST_ITEM_CREATE')
			{
				$log['checklist']++;
			}
		}

		return $items;
	}
}
<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

class AddSearchIndex
{
	public function __invoke(array $fullTaskData): void
	{
		$taskData = [
			'ID' => (int)$fullTaskData['ID'],
			'TITLE' => $fullTaskData['TITLE'],
			'DESCRIPTION' => $fullTaskData['DESCRIPTION'],
			'SE_TAG' => $fullTaskData['TAGS'],
			'GROUP_ID' => $fullTaskData['GROUP_ID'],
			'SITE_ID' => $fullTaskData['SITE_ID'],
			'CREATED_BY' => $fullTaskData['CREATED_BY'],
			'RESPONSIBLE_ID' => $fullTaskData['RESPONSIBLE_ID'],
			'ACCOMPLICES' => $fullTaskData['ACCOMPLICES'],
			'AUDITORS' => $fullTaskData['AUDITORS'],
		];
		if (!empty($fullTaskData['CHANGED_DATE']))
		{
			$taskData['CHANGED_DATE'] = $fullTaskData['CHANGED_DATE'];
		}
		if (!empty($fullTaskData['CREATED_DATE']))
		{
			$taskData['CREATED_DATE'] = $fullTaskData['CREATED_DATE'];
		}

		(new Async\Message\AddSearchIndex($taskData))->sendByTaskId((int)$fullTaskData['ID']);
	}
}

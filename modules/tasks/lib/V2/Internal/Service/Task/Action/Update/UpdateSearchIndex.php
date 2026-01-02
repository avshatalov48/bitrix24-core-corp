<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

class UpdateSearchIndex
{
	public function __invoke(array $fullTaskData, array $fields): void
	{
		$mergedFields = array_merge($fullTaskData, $fields);
		$mergedFields['SE_TAG'] = $fields['TAGS'] ?? [];

		(new Async\Message\UpdateSearchIndex($mergedFields))->sendByTaskId((int)$fullTaskData['ID']);
	}
}
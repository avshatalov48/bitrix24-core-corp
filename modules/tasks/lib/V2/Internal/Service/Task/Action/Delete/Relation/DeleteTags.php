<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation;

use Bitrix\Tasks\Internals\Task\LabelTable;

class DeleteTags
{
	public function __invoke(array $fullTaskData): void
	{
		LabelTable::deleteByFilter(['TASK_ID' => $fullTaskData['ID']]);
	}
}
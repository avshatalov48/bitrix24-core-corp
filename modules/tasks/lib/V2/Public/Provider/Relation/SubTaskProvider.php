<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Provider\Relation;

use Bitrix\Tasks\V2\Internal\Access\Task\ActionDictionary;
use Bitrix\Tasks\V2\Public\Provider\Params\Relation\RelationTaskParams;

class SubTaskProvider extends AbstractRelationTaskProvider
{
	protected function getFilter(RelationTaskParams $relationTaskParams): array
	{
		return ['=PARENT_ID' => $relationTaskParams->taskId];
	}

	protected function getRelationRights(array $taskIds, int $taskId, int $userId): array
	{
		if (empty($taskIds))
		{
			return [];
		}

		return $this->taskRightService->getTaskRightsBatch(
			userId: $userId,
			taskIds: $taskIds,
			rules: ActionDictionary::SUBTASK_ACTIONS,
		);
	}
}
